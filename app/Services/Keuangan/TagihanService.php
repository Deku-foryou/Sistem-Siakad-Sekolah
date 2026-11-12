<?php

namespace App\Services\Keuangan;

use App\Models\Denda;
use App\Models\Kelassiswa;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanAdjustment;
use App\Services\KonteksAkademikService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Pembuatan tagihan dan penurunan status.
 *
 * SPP rutin dibuat melalui scheduler (command bulanan).
 * Tagihan insidental (ujian, seragam, dll.) dibuat manual oleh admin.
 *
 * Snapshot nominal, tarif, kelas, periode disimpan pada saat terbit.
 * Perubahan tarif tidak mengubah tagihan yang sudah diterbitkan.
 */
class TagihanService
{
    public const JENIS_SPP = 'spp';

    /**
     * Terbitkan tagihan SPP untuk seluruh siswa aktif pada periode tertentu.
     *
     * Idempoten: tidak membuat ulang tagihan untuk siswa & periode yang sama.
     * Siswa baru tidak ditagih retroaktif sebelum tahun_masuk.
     * Siswa tanpa tarif berlaku dilewati (tidak dibuatkan tagihan Rp 0) dan dihitung sebagai 'tanpa_tarif'.
     *
     * @return array{dibuat:int, dilewati:int, dibebaskan:int, tanpa_tarif:int}
     */
    public static function terbitSppBulanan(?string $periode = null, ?\Illuminate\Contracts\Auth\Authenticatable $pembuat = null, bool $dryRun = false): array
    {
        $periode = $periode ?? now()->format('Y-m');
        $jadwal = self::jadwalSppBulanan($periode);
        $tanggalTerbit = $jadwal['tanggal_terbit'];
        $periodeTahun = (int) substr($periode, 0, 4);

        $siswaQuery = Siswa::query()
            ->where('status', 'aktif');

        if ($periodeTahun > 0) {
            $siswaQuery->where(function ($q) use ($periodeTahun) {
                $q->whereNull('tahun_masuk')
                    ->orWhere('tahun_masuk', '<=', $periodeTahun);
            });
        }

        $siswaIds = $siswaQuery->pluck('idsiswa');

        $dibuat = 0;
        $dilewati = 0;
        $dibebaskan = 0;
        $tanpaTarif = 0;

        foreach ($siswaIds as $siswaId) {
            $siswa = Siswa::find($siswaId);
            if (! $siswa) {
                $dilewati++;

                continue;
            }

            if ($siswa->tahun_masuk && $periodeTahun < (int) $siswa->tahun_masuk) {
                $dilewati++;

                continue;
            }

            // Idempotensi: siswa + SPP + periode yang sudah ada dilewati.
            $ada = Tagihan::query()
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->where('jenis', self::JENIS_SPP)
                ->where('periode', $periode)
                ->exists();
            if ($ada) {
                $dilewati++;

                continue;
            }

            $tagihan = self::buatTagihanRecord($siswa, self::JENIS_SPP, $periode, null, $tanggalTerbit, $jadwal['jatuh_tempo'], null, $pembuat, $dryRun);

            if ($tagihan === null) {
                if ($dryRun) {
                    $dilewati++;
                } else {
                    $tanpaTarif++;
                }

                continue;
            }

            if ($tagihan->status === 'dibebaskan') {
                $dibebaskan++;
            } else {
                $dibuat++;
            }
        }

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati, 'dibebaskan' => $dibebaskan, 'tanpa_tarif' => $tanpaTarif];
    }

    /**
     * Terbitkan tagihan manual untuk satu siswa.
     */
    public static function terbitManual(Siswa $siswa, string $jenis, string $periode, int $nominal, string $tanggalTerbit, string $jatuhTempo, ?\Illuminate\Contracts\Auth\Authenticatable $pembuat = null): Tagihan
    {
        return DB::transaction(function () use ($siswa, $jenis, $periode, $nominal, $tanggalTerbit, $jatuhTempo, $pembuat) {
            $existing = Tagihan::query()
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->where('jenis', $jenis)
                ->where('periode', $periode)
                ->whereIn('status', ['belum_dibayar', 'dibayar_sebagian', 'lunas'])
                ->first();

            if ($existing) {
                throw new DomainException('Tagihan untuk siswa, jenis, dan periode tersebut sudah ada.');
            }

            return self::buatTagihanRecord($siswa, $jenis, $periode, $nominal, $tanggalTerbit, $jatuhTempo, null, $pembuat)
                ?? throw new DomainException('Tagihan tidak dapat dibuat: siswa belum memiliki tarif berlaku.');
        });
    }

    /**
     * Terbitkan tagihan insidental untuk banyak siswa (semua siswa aktif / satu kelas / satu siswa).
     *
     * @return int jumlah tagihan dibuat
     */
    public static function terbitInsidental(string $nama, int $nominal, string $jatuhTempo, string $periode, string $scope, ?int $scopeId, ?\Illuminate\Contracts\Auth\Authenticatable $pembuat = null, string $jenis = 'insidental'): int
    {
        return DB::transaction(function () use ($nama, $nominal, $jatuhTempo, $periode, $scope, $scopeId, $pembuat, $jenis) {
            $siswaQuery = Siswa::query()->where('status', 'aktif');

            if ($scope === 'kelas' && $scopeId) {
                $siswaIds = Kelassiswa::query()
                    ->where('kelas_idkelas', $scopeId)
                    ->where('status', 'aktif')
                    ->pluck('siswa_idsiswa');
                $siswaQuery->whereIn('idsiswa', $siswaIds);
            } elseif ($scope === 'siswa' && $scopeId) {
                $siswaQuery->where('idsiswa', $scopeId);
            }

            $siswaIds = $siswaQuery->pluck('idsiswa');
            $dibuat = 0;
            $tanggalTerbit = now()->format('Y-m-d');

            foreach ($siswaIds as $siswaId) {
                $siswa = Siswa::find($siswaId);
                if (! $siswa) {
                    continue;
                }

                if (self::buatTagihanRecord($siswa, $jenis, $periode, $nominal, $tanggalTerbit, $jatuhTempo, $nama, $pembuat) !== null) {
                    $dibuat++;
                }
            }

            return $dibuat;
        });
    }

    /**
     * Batalkan tagihan (belum dibayar / dibayar sebagian dapat dibatalkan admin; lunas tidak dapat dibatalkan).
     */
    public static function batalkan(Tagihan $tagihan, \Illuminate\Contracts\Auth\Authenticatable $aktor, string $alasan): Tagihan
    {
        return DB::transaction(function () use ($tagihan, $aktor, $alasan) {
            $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->idtagihan);

            if (! in_array($tagihan->status, ['belum_dibayar', 'dibayar_sebagian'], true)) {
                throw new DomainException('Hanya tagihan yang belum lunas yang dapat dibatalkan.');
            }

            $nominalLama = $tagihan->total_tagihan;
            $tagihan->status = 'dibatalkan';
            $tagihan->alasan_batal = $alasan;
            $tagihan->dibatalkan_oleh = $aktor->id;
            $tagihan->dibatalkan_pada = now();
            $tagihan->save();

            TagihanAdjustment::create([
                'tagihan_idtagihan' => $tagihan->idtagihan,
                'jenis' => 'pembatalan',
                'nominal_lama' => $nominalLama,
                'nominal_baru' => 0,
                'alasan' => $alasan,
                'oleh' => $aktor->id,
            ]);

            return $tagihan;
        });
    }

    /**
     * Terapkan denda untuk tagihan yang lewat jatuh tempo dan belum lunas.
     * Idempotent — hanya satu denda per tagihan dan hanya jika belum ada.
     */
    public static function terapkanDendaJatuhTempo(?string $tanggal = null): int
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        $jumlah = 0;

        $kandidat = Tagihan::query()
            ->where('status', 'belum_dibayar')
            ->whereNull('denda_id')
            ->where('tanggal_jatuh_tempo', '<', $tanggal)
            ->get();

        foreach ($kandidat as $tagihan) {
            DB::transaction(function () use ($tagihan) {
                $tagihan->refresh();

                if ($tagihan->denda_id !== null) {
                    return;
                }

                $rule = self::dendaRule($tagihan->jenis);
                if (! $rule) {
                    return;
                }

                $sisaPokok = (int) $tagihan->total_tagihan;
                $nominalDenda = $rule->metode === 'nominal'
                    ? (int) $rule->nilai
                    : (int) round($sisaPokok * $rule->nilai / 100);

                if ($nominalDenda <= 0) {
                    return;
                }

                $denda = Denda::create([
                    'tagihan_idtagihan' => $tagihan->idtagihan,
                    'metode' => $rule->metode,
                    'nilai' => $rule->nilai,
                    'nominal_denda' => $nominalDenda,
                ]);

                $tagihan->denda_id = $denda->iddenda;
                $tagihan->total_tagihan = $sisaPokok + $nominalDenda;
                $tagihan->save();
            });
            $jumlah++;
        }

        return $jumlah;
    }

    /**
     * Aturan denda per jenis tagihan (hard-coded untuk sekarang; ke depan bisa dari tabel denda_konfigurasi).
     *
     * @return object{metode:string,nilai:int}|null
     */
    public static function dendaRule(string $jenis): ?object
    {
        $rules = [
            'spp' => null,
            'ujian' => ['metode' => 'persen', 'nilai' => 5],
            'pendaftaran' => null,
            'buku' => null,
            'seragam' => null,
            'insidental' => null,
        ];

        $rule = $rules[$jenis] ?? null;

        return $rule ? (object) $rule : null;
    }

    /**
     * Buat record tagihan dengan perhitungan tarif + beasiswa otomatis.
     *
     * Mengembalikan null bila:
     * - $dryRun aktif (simulasi, tidak ada tulisan), atau
     * - tidak ada tarif berlaku untuk siswa pada tanggal terbit dan tidak ada
     *   nominal eksplisit (override) — tagihan Rp 0 TIDAK dibuat (lihat 'tanpa_tarif').
     *
     * @param  int|null  $nominalOverride  bila null, tarif diambil dari resolver ($siswa, $tanggalTerbit)
     */
    private static function buatTagihanRecord(Siswa $siswa, string $jenis, string $periode, ?int $nominalOverride, string $tanggalTerbit, string $jatuhTempo, ?string $nama, ?\Illuminate\Contracts\Auth\Authenticatable $pembuat, bool $dryRun = false): ?Tagihan
    {
        $hitung = DiskonService::hitung($siswa, $tanggalTerbit);

        // Tanpa tarif berlaku & tanpa nominal eksplisit: jangan membuat tagihan Rp 0.
        // Pemanggil (SPP) akan menghitung sebagai 'tanpa_tarif' (dilewati, bisa diterbitkan ulang setelah tarif diperbaiki).
        if ($nominalOverride === null && ($hitung['tarif_idtarif'] ?? null) === null) {
            return null;
        }

        if ($dryRun) {
            return null;
        }

        // Satu jalur perhitungan untuk manual dan otomatis (anti duplikasi logika):
        // - nominal = eksplisit admin (override) atau tarif hasil resolver;
        // - diskon  = beasiswa yang dihitung resolver, dibatasi 100% dari nominal;
        // - custom tarif individu mengesampingkan beasiswa (ditangani DiskonService);
        // - pembebasan penuh -> total 0 -> status dibebaskan.
        $nominal = $nominalOverride !== null ? $nominalOverride : (int) ($hitung['tarif_nominal'] ?? 0);
        $pembebasan = (bool) ($hitung['pembebasan'] ?? false);
        $diskonPersen = (int) ($hitung['diskon_persen'] ?? 0);
        $diskonNominal = 0;

        if ($pembebasan) {
            $diskonNominal = $nominal;
            $diskonPersen = 100;
        } elseif ($diskonPersen > 0) {
            $diskonNominal = min($nominal, (int) round($nominal * $diskonPersen / 100));
        } else {
            $diskonNominal = min($nominal, (int) ($hitung['diskon_nominal'] ?? 0));
        }

        // Batasi diskon tidak melebihi nominal (100% maksimal).
        $diskonNominal = min($diskonNominal, $nominal);

        $total = max(0, $nominal - $diskonNominal);
        $status = $pembebasan ? 'dibebaskan' : 'belum_dibayar';

        $tarifId = $hitung['tarif_idtarif'] ?? null;
        $tarif = $tarifId ? \App\Models\Tarif::find($tarifId) : null;
        $kelasId = $tarif && $tarif->kelas_idkelas ? $tarif->kelas_idkelas : null;
        $kelas = $kelasId ? \App\Models\Kelas::find($kelasId) : null;
        $konteks = KonteksAkademikService::ambil();

        return Tagihan::create([
            'siswa_idsiswa' => $siswa->idsiswa,
            'jenis' => $jenis,
            'nama' => $nama,
            'periode' => $periode,
            'nominal' => $nominal,
            'diskon_nominal' => $diskonNominal,
            'diskon_persen' => $diskonPersen,
            'total_tagihan' => $total,
            'tarif_idtarif' => $tarif?->idtarif,
            'tarif_nama' => $tarif?->nama,
            'tarif_jenis' => $tarif?->jenis,
            'tarif_nominal' => $tarif?->nominal,
            'kelas_idkelas' => $kelas?->idkelas,
            'kelas_nama' => $kelas?->nama,
            'tahunajaran_idtahunajaran' => $konteks->tahunAjaran?->idtahunajaran,
            'semester_idsemester' => $konteks->semester?->idsemester,
            'status' => $status,
            'tanggal_terbit' => $tanggalTerbit,
            'tanggal_jatuh_tempo' => $jatuhTempo,
            'dibuat_oleh' => $pembuat?->id,
        ]);
    }

    private static function jadwalSppBulanan(string $periode): array
    {
        [$tahun, $bulan] = explode('-', $periode);
        $awal = new \DateTimeImmutable("{$tahun}-{$bulan}-01");

        return [
            'tanggal_terbit' => $awal->format('Y-m-01'),
            'jatuh_tempo' => $awal->modify('+9 days')->format('Y-m-d'),
        ];
    }
}
