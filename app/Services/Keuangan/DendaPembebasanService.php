<?php

namespace App\Services\Keuangan;

use App\Models\Denda;
use App\Models\DendaPengajuan;
use App\Models\Tagihan;
use App\Models\TagihanAdjustment;
use App\Services\Payments\PaymentService;
use DomainException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pembebasan denda dua tahap: admin mengajukan → superadmin menyetujui/menolak.
 *
 * Aturan:
 * - Tidak ada perubahan finansial sebelum persetujuan superadmin (denda tetap berlaku).
 * - Superadmin tidak dapat menyetujui pengajuannya sendiri.
 * - Denda yang dibebaskan tidak dicatat sebagai pembayaran (tidak ada baris pembayaran/alokasi).
 * - Pembebasan hanya menyentuh denda yang belum tertutupi pembayaran:
 *   penurunan = min(nominal_denda, max(0, total_tagihan − terpakai)).
 * - Sisa tagihan tidak pernah menjadi negatif; status tagihan dihitung ulang dari alokasi.
 * - Semua operasi atomik (DB::transaction + lockForUpdate); audit trail lengkap.
 */
class DendaPembebasanService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_TERPROSES = 'terproses';

    public const STATUS_GAGAL = 'gagal';

    /**
     * Berapa dari nominal denda yang masih dapat dibebaskan (belum tertutupi pembayaran).
     */
    public static function nominalDapatDibebaskan(Denda $denda, Tagihan $tagihan): int
    {
        $terpakai = \App\Services\Payments\PaymentService::terpakaiTagihan($tagihan);
        $sisaTagihan = (int) $tagihan->total_tagihan - $terpakai;

        return max(0, min((int) $denda->nominal_denda, $sisaTagihan));
    }

    /**
     * Pengajuan pembebasan denda (admin/superadmin).
     */
    public static function ajukanPembebasanDenda(Tagihan $tagihan, string $alasan, Authenticatable $aktor): DendaPengajuan
    {
        $aktorLevel = $aktor->level ?? null;
        if (! in_array($aktorLevel, ['superadmin', 'admin'], true)) {
            throw new DomainException('Hanya admin atau superadmin yang dapat mengajukan pembebasan denda.');
        }

        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new DomainException('Alasan pembebasan denda wajib diisi.');
        }

        return DB::transaction(function () use ($tagihan, $alasan, $aktor) {
            // Urutan kunci konsisten: siswa → pembayaran → alokasi → tagihan → denda → pengajuan
            $tagihanLocked = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->idtagihan);
            $denda = Denda::query()->where('tagihan_idtagihan', $tagihanLocked->idtagihan)->lockForUpdate()->first();

            if (! $denda) {
                throw new DomainException('Tagihan ini tidak memiliki denda.');
            }

            if ($denda->status !== 'berlaku') {
                throw new DomainException('Denda ini sudah dibebaskan; tidak dapat diajukan lagi.');
            }

            if ($tagihanLocked->status === 'lunas') {
                throw new DomainException('Tagihan sudah lunas; pembebasan denda tidak dapat diajukan.');
            }

            if (! in_array($tagihanLocked->status, ['belum_dibayar', 'dibayar_sebagian'], true)) {
                throw new DomainException('Tagihan tidak dapat diajukan pembebasan denda pada status ini.');
            }

            $adaAktif = DendaPengajuan::query()
                ->where('denda_iddenda', $denda->iddenda)
                ->where('status', self::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();
            if ($adaAktif) {
                throw new DomainException('Masih ada pengajuan pembebasan denda aktif untuk denda ini.');
            }

            $penurunan = self::nominalDapatDibebaskan($denda, $tagihanLocked);
            if ($penurunan <= 0) {
                throw new DomainException('Denda sudah tertutupi sisa pembayaran; pembebasan tidak dapat diterapkan.');
            }

            return DendaPengajuan::create([
                'denda_iddenda' => $denda->iddenda,
                'tagihan_idtagihan' => $tagihanLocked->idtagihan,
                'siswa_idsiswa' => $tagihanLocked->siswa_idsiswa,
                'nominal_denda' => $penurunan,
                'alasan' => $alasan,
                'status' => self::STATUS_PENDING,
                'diajukan_oleh' => $aktor->id,
            ]);
        });
    }

    /**
     * Setujui (dan terapkan) pembebasan denda — superadmin saja.
     */
    public static function setujuiPembebasanDenda(DendaPengajuan $pengajuan, Authenticatable $aktor): DendaPengajuan
    {
        $aktorLevel = $aktor->level ?? null;
        if ($aktorLevel !== 'superadmin') {
            throw new DomainException('Hanya superadmin yang dapat menyetujui pembebasan denda.');
        }

        return DB::transaction(function () use ($pengajuan, $aktor) {
            $pengajuan = DendaPengajuan::query()->lockForUpdate()->findOrFail($pengajuan->id);

            if ($pengajuan->status === self::STATUS_TERPROSES) {
                // Idempotensi: sudah diterapkan — jangan proses ulang
                return $pengajuan->fresh();
            }

            if ($pengajuan->status !== self::STATUS_PENDING) {
                throw new DomainException('Pembebasan denda hanya dapat disetujui dari status menunggu.');
            }

            // Superadmin tidak dapat menyetujui pengajuan yang diajukannya sendiri
            if ((int) $pengajuan->diajukan_oleh === (int) $aktor->id) {
                throw new DomainException('Superadmin tidak dapat menyetujui pengajuan yang diajukannya sendiri.');
            }

            $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($pengajuan->tagihan_idtagihan);
            $denda = Denda::query()->lockForUpdate()->findOrFail($pengajuan->denda_iddenda);

            if ($denda->status !== 'berlaku') {
                throw new DomainException('Denda ini sudah dibebaskan; pengajuan tidak dapat diterapkan.');
            }

            $terpakai = PaymentService::terpakaiTagihan($tagihan);
            $penurunan = max(0, min((int) $denda->nominal_denda, (int) $tagihan->total_tagihan - $terpakai));
            if ($penurunan <= 0) {
                throw new DomainException('Pembebasan tidak dapat diterapkan: denda sudah tertutupi sisa pembayaran.');
            }

            $totalLama = (int) $tagihan->total_tagihan;
            $tagihan->total_tagihan = $totalLama - $penurunan;

            if ($terpakai >= $tagihan->total_tagihan && $tagihan->total_tagihan > 0) {
                $tagihan->status = 'lunas';
            } elseif ($terpakai > 0) {
                $tagihan->status = 'dibayar_sebagian';
            } else {
                $tagihan->status = 'belum_dibayar';
            }
            $tagihan->save();

            $denda->status = 'dibebaskan';
            $denda->dibebaskan_pada = now();
            $denda->dibebaskan_oleh = $aktor->id;
            $denda->alasan_dibebaskan = $pengajuan->alasan;
            $denda->save();

            TagihanAdjustment::create([
                'tagihan_idtagihan' => $tagihan->idtagihan,
                'jenis' => 'denda_dibebaskan',
                'nominal_lama' => $totalLama,
                'nominal_baru' => $tagihan->total_tagihan,
                'alasan' => 'Pembebasan denda (pengajuan #'.$pengajuan->id.'): '.$pengajuan->alasan,
                'oleh' => $aktor->id,
            ]);

            $pengajuan->status = self::STATUS_TERPROSES;
            $pengajuan->diproses_oleh = $aktor->id;
            $pengajuan->diproses_pada = now();
            $pengajuan->catatan_proses = 'Pembebasan denda Rp '.number_format($penurunan, 0, ',', '.').' diterapkan; tagihan '.$totalLama.' → '.$tagihan->total_tagihan.'.';
            $pengajuan->save();

            Log::info('pembebasan denda diterapkan', [
                'denda_pengajuan_id' => $pengajuan->id,
                'tagihan_idtagihan' => $tagihan->idtagihan,
                'denda_iddenda' => $denda->iddenda,
                'nominal_denda' => $penurunan,
                'total_tagihan_lama' => $totalLama,
                'total_tagihan_baru' => $tagihan->total_tagihan,
                'oleh' => $aktor->id,
            ]);

            return $pengajuan->fresh();
        });
    }

    /**
     * Tolak pembebasan denda — superadmin saja, alasan wajib.
     */
    public static function tolakPembebasanDenda(DendaPengajuan $pengajuan, string $alasan, Authenticatable $aktor): DendaPengajuan
    {
        $aktorLevel = $aktor->level ?? null;
        if ($aktorLevel !== 'superadmin') {
            throw new DomainException('Hanya superadmin yang dapat menolak pembebasan denda.');
        }

        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($pengajuan, $alasan, $aktor) {
            $pengajuan = DendaPengajuan::query()->lockForUpdate()->findOrFail($pengajuan->id);

            if ($pengajuan->status !== self::STATUS_PENDING) {
                throw new DomainException('Pembebasan denda hanya dapat ditolak dari status menunggu.');
            }

            $pengajuan->status = self::STATUS_DITOLAK;
            $pengajuan->alasan_ditolak = $alasan;
            $pengajuan->diproses_oleh = $aktor->id;
            $pengajuan->diproses_pada = now();
            $pengajuan->save();

            return $pengajuan->fresh();
        });
    }

    /**
     * Daftar tagihan yang memiliki denda berlaku (kandidat pengajuan).
     */
    public static function tagihanDenganDendaBerlaku(?string $search = null)
    {
        $query = Tagihan::query()
            ->whereHas('denda', fn ($q) => $q->where('status', 'berlaku'))
            ->whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])
            ->with(['siswa', 'denda'])
            ->orderByDesc('tanggal_terbit');

        if ($search) {
            $s = trim($search);
            $query->whereHas('siswa', fn ($sq) => $sq->where('nama', 'like', "%{$s}%")->orWhere('nis', 'like', "%{$s}%"));
        }

        return $query;
    }
}
