<?php

namespace App\Services\Keuangan;

use App\Models\Kuitansi;
use App\Models\PaymentAttempt;
use App\Models\Pembayaran;
use App\Models\PembayaranAlokasi;
use App\Models\RekonsiliasiHasil;
use App\Models\RekonsiliasiTemuan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rekonsiliasi keuangan berbasis ledger SIAKAD (pembayaran + pembayaran_alokasi).
 *
 * - Read-only: tidak mengubah ledger, transaksi, kuitansi, saldo siswa.
 * - Menghasilkan satu RekonsiliasiHasil + RekonsiliasiTemuan (tabel terpisah, bukan ledger).
 * - Temuan yang diperbolehkan TIDAK dilaporkan: attempt pending, pembayaran parsial,
 *   saldo masuk yang tidak digunakan, kuitansi manual yang belum dibuat sebelum verifikasi.
 * - Setiap pemeriksaan dibatasi 500 temuan per kategori agar aman untuk data besar.
 *   Bila batas tercapai, temuan yang tidak tersimpan DITUTULISKAN di ringkasan
 *   ('terpotong' => kategori => jumlah_sebenarnya) sehingga UI dapat memunculkan
 *   peringatan "hasil terbatas/truncated".
 */
class RekonsiliasiKeuanganService
{
    public const KATEGORI_ATTEMPT_SUKSES_TANPA_LEDGER = 'ATTEMPT_SUKSES_TANPA_LEDGER';

    public const KATEGORI_LEDGER_ONLINE_TANPA_ATTEMPT = 'LEDGER_ONLINE_TANPA_ATTEMPT';

    public const KATEGORI_SUCCESS_TANPA_ALOKASI = 'PEMBAYARAN_SUCCESS_TANPA_ALOKASI';

    public const KATEGORI_ALOKASI_MELEBIHI_NOMINAL = 'ALOKASI_MELEBIHI_NOMINAL';

    public const KATEGORI_ALOKASI_TAGIHAN_SALAH_PEMILIK = 'ALOKASI_TAGIHAN_SALAH_PEMILIK';

    public const KATEGORI_TAGIHAN_STATUS_ALOKASI_TIDAK_COCOK = 'TAGIHAN_STATUS_ALOKASI_TIDAK_COCOK';

    public const KATEGORI_SALDO_NEGATIF = 'SALDO_NEGATIF';

    public const KATEGORI_KUITANSI_TANPA_PEMBAYARAN_VALID = 'KUITANSI_TANPA_PEMBAYARAN_VALID';

    public const KATEGORI_KUITANSI_DATA_TIDAK_COCOK = 'KUITANSI_DATA_TIDAK_COCOK';

    public const KATEGORI_SUCCESS_TANPA_KUITANSI = 'PEMBAYARAN_VERIFIED_TANPA_KUITANSI';

    public const KATEGORI_MANUAL_VERIFIKASI_TIDAK_KONSISTEN = 'MANUAL_VERIFIKASI_TIDAK_KONSISTEN';

    public const KATEGORI_REVERSAL_TANPA_REFERENSI = 'REVERSAL_TANPA_REFERENSI';

    public const KATEGORI_REVERSAL_MELEBIHI_ALOKASI = 'REVERSAL_MELEBIHI_ALOKASI';

    public const LABEL_KATEGORI = [
        self::KATEGORI_ATTEMPT_SUKSES_TANPA_LEDGER => 'Attempt sukses tanpa ledger pembayaran',
        self::KATEGORI_LEDGER_ONLINE_TANPA_ATTEMPT => 'Ledger pembayaran online tanpa attempt',
        self::KATEGORI_SUCCESS_TANPA_ALOKASI => 'Pembayaran terverifikasi tanpa alokasi',
        self::KATEGORI_ALOKASI_MELEBIHI_NOMINAL => 'Alokasi melebihi nominal pembayaran',
        self::KATEGORI_ALOKASI_TAGIHAN_SALAH_PEMILIK => 'Alokasi pada tagihan siswa lain',
        self::KATEGORI_TAGIHAN_STATUS_ALOKASI_TIDAK_COCOK => 'Status tagihan tidak cocok dengan alokasi',
        self::KATEGORI_SALDO_NEGATIF => 'Saldo siswa negatif',
        self::KATEGORI_KUITANSI_TANPA_PEMBAYARAN_VALID => 'Kuitansi tanpa pembayaran valid',
        self::KATEGORI_KUITANSI_DATA_TIDAK_COCOK => 'Kuitansi tidak cocok dengan pembayaran/tagihan',
        self::KATEGORI_SUCCESS_TANPA_KUITANSI => 'Pembayaran terverifikasi tanpa kuitansi',
        self::KATEGORI_MANUAL_VERIFIKASI_TIDAK_KONSISTEN => 'Status pembayaran manual tidak konsisten dengan histori verifikasi',
        self::KATEGORI_REVERSAL_TANPA_REFERENSI => 'Reversal tanpa referensi pengajuan',
        self::KATEGORI_REVERSAL_MELEBIHI_ALOKASI => 'Reversal melebihi alokasi asli',
    ];

    public const STATUS_INFO = 'info';

    public const STATUS_KRTIS = 'kritis';

    public const STATUS_TINJAU = 'perlu_ditinjau';

    /** Batas temuan per kategori agar UI/penyimpanan tidak bermasalah pada data besar. */
    private const BATAS_PER_KATEGORI = 500;

    /**
     * Jalankan satu kali pemeriksaan rekonsiliasi dan SIMPAN hasilnya.
     *
     * @param  string|null  $periode  YYYY-MM untuk membatasi cakupan; null = semua data
     */
    public static function jalankanPemeriksaan(?string $periode, ?User $aktor): RekonsiliasiHasil
    {
        $pemeriksaan = 13;

        $paket = [
            self::cekAttemptSuksesTanpaLedger($periode),
            self::cekLedgerOnlineTanpaAttempt($periode),
            self::cekSuccessTanpaAlokasi($periode),
            self::cekAlokasiMelebihiNominal($periode),
            self::cekAlokasiTagihanSalahPemilik($periode),
            self::cekTagihanStatusAlokasiTidakCocok($periode),
            self::cekSaldoNegatif($periode),
            self::cekKuitansiTanpaPembayaranValid($periode),
            self::cekKuitansiDataTidakCocok($periode),
            self::cekSuccessTanpaKuitansi($periode),
            self::cekManualVerifikasiTidakKonsisten($periode),
            self::cekReversalTanpaReferensi($periode),
            self::cekReversalMelebihiAlokasi($periode),
        ];

        $temuan = [];
        $totalSemua = 0;
        $terpotong = [];
        $perKategori = [];
        foreach ($paket as $p) {
            $kategori = $p['kategori'];
            $totalSemua += (int) $p['total'];
            $perKategori[$kategori] = (int) $p['total'];
            if ($p['terpotong']) {
                $terpotong[$kategori] = (int) $p['total'];
            }
            $temuan = array_merge($temuan, $p['temuan']);
        }

        // Dedup dalam run
        $seen = [];
        $unik = [];
        foreach ($temuan as $t) {
            $key = $t['kategori'].'|'.($t['referensi_tipe'] ?? '').'|'.($t['referensi_id'] ?? '').'|'.($t['tagihan_idtagihan'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unik[] = $t;
        }

        $jumlahKritis = collect($unik)->where('status', self::STATUS_KRTIS)->count();

        // Ringkasan: jumlah ditemukan (bisa > tersimpan bila terpotong), jumlah tersimpan, terpotong
        $ringkasan = [
            'per_kategori' => $perKategori,
            'total_ditemukan' => $totalSemua,
            'total_tersimpan' => count($unik),
            'terpotong' => $terpotong,
        ];

        return DB::transaction(function () use ($periode, $aktor, $unik, $jumlahKritis, $pemeriksaan, $ringkasan) {
            $hasil = RekonsiliasiHasil::create([
                'periode' => $periode,
                'dijalankan_oleh' => $aktor?->id,
                'dijalankan_pada' => now(),
                'jumlah_pemeriksaan' => $pemeriksaan,
                'jumlah_temuan' => count($unik),
                'jumlah_kritis' => $jumlahKritis,
                'ringkasan' => $ringkasan,
            ]);

            foreach ($unik as $t) {
                RekonsiliasiTemuan::create(array_merge($t, ['rekonsiliasi_hasil_id' => $hasil->id]));
            }

            Log::info('rekonsiliasi keuangan dijalankan', [
                'rekonsiliasi_hasil_id' => $hasil->id,
                'periode' => $periode,
                'jumlah_temuan' => count($unik),
                'jumlah_kritis' => $jumlahKritis,
                'total_ditemukan' => $ringkasan['total_ditemukan'],
                'terpotong' => array_keys($ringkasan['terpotong']),
                'oleh' => $aktor?->id,
            ]);

            return $hasil->fresh('temuan');
        });
    }

    public static function labelKategori(string $kategori): string
    {
        return self::LABEL_KATEGORI[$kategori] ?? $kategori;
    }

    /**
     * Normalisasi kolom ringkasan hasil rekonsiliasi.
     *
     * Toleran terhadap:
     * - array (cast 'array' aktif, alur normal),
     * - JSON string valid (bila cast tidak aktif / data lama),
     * - double-encoded JSON (nilai tersimpan berupa string berisi JSON, mis. hasil tulis salah),
     * - null/kosong.
     *
     * @return array<string,mixed>
     */
    private static function normalisasiRingkasan(mixed $ringkasan): array
    {
        $value = $ringkasan;

        // Decode berulang hingga stabil (maks 3 level) untuk kasus double/triple-encoded JSON.
        for ($i = 0; $i < 3 && is_string($value); $i++) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            $value = $decoded;
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Apakah hasil pemeriksaan ini terpotong (lebih dari BATAS_PER_KATEGORI temuan pada kategori tertentu)?
     *
     * @return array<string,int> kategori => total sebenarnya
     */
    public static function terpotongPada(RekonsiliasiHasil $hasil): array
    {
        $ringkasan = self::normalisasiRingkasan($hasil->ringkasan ?? null);
        $terpotong = $ringkasan['terpotong'] ?? [];

        // Nilai JSON bisa kembali sebagai string — normalkan ke int agar number_format aman.
        return array_map(fn ($v) => (int) (is_numeric($v) ? $v : 0), (array) $terpotong);
    }

    public static function totalDitemukanPada(RekonsiliasiHasil $hasil): int
    {
        $ringkasan = self::normalisasiRingkasan($hasil->ringkasan ?? null);

        return (int) ($ringkasan['total_ditemukan'] ?? $hasil->jumlah_temuan);
    }

    /**
     * Hasil per kategori (jumlah ditemukan), aman untuk hasil lama yang hanya menyimpan map kategori=>jumlah.
     *
     * @return array<string,int> kategori => jumlah ditemukan
     */
    public static function perKategoriPada(RekonsiliasiHasil $hasil): array
    {
        $ringkasan = self::normalisasiRingkasan($hasil->ringkasan ?? null);

        $perKategori = $ringkasan['per_kategori'] ?? (array) $ringkasan; // fallback untuk format lama (kategori=>jumlah)

        // Nilai JSON (atau map kategori=>jumlah lama) bisa berupa string — normalkan ke int.
        return array_map(fn ($v) => (int) (is_numeric($v) ? $v : 0), (array) $perKategori);
    }

    private static function basisPeriodeTanggal($query, string $kolom, ?string $periode)
    {
        if ($periode !== null && $periode !== '') {
            $query->whereYear($kolom, (int) substr($periode, 0, 4))
                ->whereMonth($kolom, (int) substr($periode, 5, 2));
        }

        return $query;
    }

    /**
     * Ambil maksimal BATAS_PER_KATEGORI temuan tapi tetap hitung total sebenarnya.
     */
    private static function ambilTemuanTerbatas($query, array $select, ?callable $map = null, ?callable $format = null): array
    {
        $total = (int) $query->count();
        $rows = $query->limit(self::BATAS_PER_KATEGORI)->get($select);

        return ['rows' => $rows, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // ---------------------------------------------------------------- 1. Attempt sukses tanpa ledger

    private static function cekAttemptSuksesTanpaLedger(?string $periode): array
    {
        $query = PaymentAttempt::query()
            ->where('status', 'success')
            ->whereDoesntHave('pembayaran');
        $query = self::basisPeriodeTanggal($query, 'created_at', $periode);

        $total = (int) $query->count();
        $rows = $query->orderBy('idpayment_attempt')->limit(self::BATAS_PER_KATEGORI)
            ->get(['idpayment_attempt', 'siswa_idsiswa', 'tagihan_idtagihan', 'nominal', 'order_id']);

        $temuan = [];
        foreach ($rows as $a) {
            $temuan[] = [
                'kategori' => self::KATEGORI_ATTEMPT_SUKSES_TANPA_LEDGER,
                'referensi_tipe' => 'payment_attempt',
                'referensi_id' => $a->idpayment_attempt,
                'siswa_idsiswa' => $a->siswa_idsiswa,
                'tagihan_idtagihan' => $a->tagihan_idtagihan,
                'nominal_selisih' => (int) $a->nominal,
                'keterangan' => "Attempt sukses (order {$a->order_id}) tidak memiliki ledger pembayaran.",
                'status' => self::STATUS_KRTIS,
            ];
        }

        return ['kategori' => self::KATEGORI_ATTEMPT_SUKSES_TANPA_LEDGER, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // --------------------------------------------------------- 2. Ledger online tanpa attempt

    private static function cekLedgerOnlineTanpaAttempt(?string $periode): array
    {
        // provider 'koreksi' adalah replacement internal dari koreksi yang sah (bukan pembayaran online baru)
        $query = Pembayaran::query()
            ->where('status', 'success')
            ->whereNotIn('provider', ['manual', 'saldo', 'koreksi'])
            ->whereNull('payment_attempt_id');
        $query = self::basisPeriodeTanggal($query, 'dibayar_pada', $periode);

        $total = (int) $query->count();
        $rows = $query->orderBy('idpembayaran')->limit(self::BATAS_PER_KATEGORI)
            ->get(['idpembayaran', 'siswa_idsiswa', 'tagihan_idtagihan', 'nominal_dibayar', 'provider']);

        $temuan = [];
        foreach ($rows as $p) {
            $temuan[] = [
                'kategori' => self::KATEGORI_LEDGER_ONLINE_TANPA_ATTEMPT,
                'referensi_tipe' => 'pembayaran',
                'referensi_id' => $p->idpembayaran,
                'siswa_idsiswa' => $p->siswa_idsiswa,
                'tagihan_idtagihan' => $p->tagihan_idtagihan,
                'nominal_selisih' => (int) $p->nominal_dibayar,
                'keterangan' => "Pembayaran online provider {$p->provider} tanpa payment_attempt terkait.",
                'status' => self::STATUS_TINJAU,
            ];
        }

        return ['kategori' => self::KATEGORI_LEDGER_ONLINE_TANPA_ATTEMPT, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // ---------------------------------------------------- 3. Pembayaran success tanpa alokasi

    private static function cekSuccessTanpaAlokasi(?string $periode): array
    {
        $query = Pembayaran::query()->where('status', 'success')->whereDoesntHave('alokasi');
        $query = self::basisPeriodeTanggal($query, 'dibayar_pada', $periode);

        $total = (int) $query->count();
        $rows = $query->orderBy('idpembayaran')->limit(self::BATAS_PER_KATEGORI)
            ->get(['idpembayaran', 'siswa_idsiswa', 'tagihan_idtagihan', 'nominal_dibayar', 'provider']);

        $temuan = [];
        foreach ($rows as $p) {
            $temuan[] = [
                'kategori' => self::KATEGORI_SUCCESS_TANPA_ALOKASI,
                'referensi_tipe' => 'pembayaran',
                'referensi_id' => $p->idpembayaran,
                'siswa_idsiswa' => $p->siswa_idsiswa,
                'tagihan_idtagihan' => $p->tagihan_idtagihan,
                'nominal_selisih' => (int) $p->nominal_dibayar,
                'keterangan' => "Pembayaran terverifikasi provider {$p->provider} tidak memiliki alokasi.",
                'status' => self::STATUS_KRTIS,
            ];
        }

        return ['kategori' => self::KATEGORI_SUCCESS_TANPA_ALOKASI, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // ------------------------------------------------------- 4. Alokasi melebihi nominal

    private static function cekAlokasiMelebihiNominal(?string $periode): array
    {
        $grouped = DB::table('pembayaran_alokasi')
            ->select('pembayaran_idpembayaran', DB::raw("SUM(CASE WHEN jenis IN ('tagihan','saldo') THEN nominal WHEN jenis IN ('reversal-refund','reversal-koreksi') THEN -nominal ELSE 0 END) as total_alokasi"))
            ->groupBy('pembayaran_idpembayaran')
            ->havingRaw("SUM(CASE WHEN jenis IN ('tagihan','saldo') THEN nominal WHEN jenis IN ('reversal-refund','reversal-koreksi') THEN -nominal ELSE 0 END) > (SELECT nominal_dibayar FROM pembayaran WHERE idpembayaran = pembayaran_alokasi.pembayaran_idpembayaran)");

        $total = (int) DB::query()->fromSub($grouped, 'g')->count();

        $rows = $grouped->limit(self::BATAS_PER_KATEGORI)->get();

        $temuan = [];
        foreach ($rows as $row) {
            $pembayaran = Pembayaran::find($row->pembayaran_idpembayaran);
            if (! $pembayaran) {
                continue;
            }

            $selisih = (int) $row->total_alokasi - (int) $pembayaran->nominal_dibayar;
            $temuan[] = [
                'kategori' => self::KATEGORI_ALOKASI_MELEBIHI_NOMINAL,
                'referensi_tipe' => 'pembayaran',
                'referensi_id' => $pembayaran->idpembayaran,
                'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                'tagihan_idtagihan' => $pembayaran->tagihan_idtagihan,
                'nominal_selisih' => $selisih,
                'keterangan' => "Alokasi ({$row->total_alokasi}) melebihi nominal pembayaran ({$pembayaran->nominal_dibayar}) sebesar {$selisih}.",
                'status' => self::STATUS_KRTIS,
            ];
        }

        return ['kategori' => self::KATEGORI_ALOKASI_MELEBIHI_NOMINAL, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // ------------------------------------------- 5. Alokasi tagihan tetapi pemilik siswa berbeda

    private static function cekAlokasiTagihanSalahPemilik(?string $periode): array
    {
        $query = PembayaranAlokasi::query()
            ->join('tagihan', 'tagihan.idtagihan', '=', 'pembayaran_alokasi.tagihan_idtagihan')
            ->where('pembayaran_alokasi.jenis', 'tagihan')
            ->whereColumn('pembayaran_alokasi.siswa_idsiswa', '!=', 'tagihan.siswa_idsiswa');

        $total = (int) $query->count();
        $rows = $query->select('pembayaran_alokasi.idpembayaran_alokasi', 'pembayaran_alokasi.siswa_idsiswa', 'pembayaran_alokasi.tagihan_idtagihan', 'tagihan.siswa_idsiswa as pemilik_tagihan', 'pembayaran_alokasi.nominal')
            ->limit(self::BATAS_PER_KATEGORI)
            ->get();

        $temuan = [];
        foreach ($rows as $r) {
            $temuan[] = [
                'kategori' => self::KATEGORI_ALOKASI_TAGIHAN_SALAH_PEMILIK,
                'referensi_tipe' => 'alokasi',
                'referensi_id' => $r->idpembayaran_alokasi,
                'siswa_idsiswa' => $r->siswa_idsiswa,
                'tagihan_idtagihan' => $r->tagihan_idtagihan,
                'nominal_selisih' => (int) $r->nominal,
                'keterangan' => "Alokasi untuk tagihan milik siswa {$r->pemilik_tagihan} tetapi dialokasikan ke siswa {$r->siswa_idsiswa}.",
                'status' => self::STATUS_KRTIS,
            ];
        }

        return ['kategori' => self::KATEGORI_ALOKASI_TAGIHAN_SALAH_PEMILIK, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // --------------------------- 6. Status tagihan tidak cocok dengan total alokasi

    private static function cekTagihanStatusAlokasiTidakCocok(?string $periode): array
    {
        $query = Tagihan::query();
        if ($periode !== null && $periode !== '') {
            $query->where('periode', 'like', $periode.'-%');
        }

        // Hitung ketidakcocokan dengan scan terbatas dan hitung akurat via iterasi;
        // jumlah sebenarnya dicatat meskipun temuan yang disimpan dibatasi BATAS_PER_KATEGORI.
        $rows = $query->select(['idtagihan', 'siswa_idsiswa', 'total_tagihan', 'status'])
            ->limit(self::BATAS_PER_KATEGORI * 5) // buffer lebih besar untuk akurasi hitung
            ->get();

        $temuan = [];
        $totalSemua = 0;
        foreach ($rows as $t) {
            $masuk = (int) PembayaranAlokasi::query()
                ->where('siswa_idsiswa', $t->siswa_idsiswa)
                ->where('tagihan_idtagihan', $t->idtagihan)
                ->where('jenis', 'tagihan')
                ->sum('nominal');
            $reversal = (int) PembayaranAlokasi::query()
                ->where('siswa_idsiswa', $t->siswa_idsiswa)
                ->where('tagihan_idtagihan', $t->idtagihan)
                ->whereIn('jenis', ['reversal-refund', 'reversal-koreksi'])
                ->sum('nominal');
            $terpakai = max(0, $masuk - $reversal);

            $totalT = (int) $t->total_tagihan;
            $status = (string) $t->status;
            $buruk = false;

            if ($status === 'lunas' && $terpakai < $totalT) {
                $buruk = true;
                $keterangan = "Tagihan berstatus 'lunas' tetapi alokasi hanya {$terpakai} dari total {$totalT}.";
                $nominalSelisih = $totalT - $terpakai;
                $severity = self::STATUS_KRTIS;
            } elseif ($status === 'belum_dibayar' && $terpakai > 0) {
                $buruk = true;
                $keterangan = "Tagihan berstatus 'belum_dibayar' tetapi sudah memiliki alokasi {$terpakai}.";
                $nominalSelisih = $terpakai;
                $severity = self::STATUS_KRTIS;
            } elseif ($status === 'dibayar_sebagian' && ($terpakai <= 0 || $terpakai >= $totalT)) {
                $buruk = true;
                $keterangan = "Tagihan berstatus 'dibayar_sebagian' tetapi alokasi {$terpakai} dari total {$totalT}.";
                $nominalSelisih = $terpakai;
                $severity = self::STATUS_TINJAU;
            } elseif ($status === 'dibebaskan' && $terpakai > 0) {
                $buruk = true;
                $keterangan = "Tagihan berstatus 'dibebaskan' tetapi memiliki alokasi pinjam {$terpakai}.";
                $nominalSelisih = $terpakai;
                $severity = self::STATUS_TINJAU;
            }

            if (! $buruk) {
                continue;
            }

            $totalSemua++;
            if (count($temuan) < self::BATAS_PER_KATEGORI) {
                $temuan[] = [
                    'kategori' => self::KATEGORI_TAGIHAN_STATUS_ALOKASI_TIDAK_COCOK,
                    'referensi_tipe' => 'tagihan',
                    'referensi_id' => $t->idtagihan,
                    'siswa_idsiswa' => $t->siswa_idsiswa,
                    'tagihan_idtagihan' => $t->idtagihan,
                    'nominal_selisih' => $nominalSelisih,
                    'keterangan' => $keterangan,
                    'status' => $severity,
                ];
            }
        }

        $jumlahDiiterasi = $rows->count();

        return ['kategori' => self::KATEGORI_TAGIHAN_STATUS_ALOKASI_TIDAK_COCOK, 'temuan' => $temuan, 'total' => $totalSemua, 'terpotong' => $totalSemua >= self::BATAS_PER_KATEGORI || $jumlahDiiterasi >= self::BATAS_PER_KATEGORI * 5];
    }

    // ------------------------------------------------------ 7. Saldo siswa negatif

    private static function cekSaldoNegatif(?string $periode): array
    {
        $masuk = PembayaranAlokasi::query()
            ->select('siswa_idsiswa', DB::raw('SUM(nominal) as masuk'))
            ->where('jenis', 'saldo')
            ->whereNull('tagihan_idtagihan')
            ->groupBy('siswa_idsiswa');

        $dipakai = PembayaranAlokasi::query()
            ->select('siswa_idsiswa', DB::raw('SUM(nominal) as dipakai'))
            ->where('jenis', 'tagihan')
            ->whereHas('pembayaran', fn ($q) => $q->where('provider', 'saldo'))
            ->groupBy('siswa_idsiswa');

        $reversal = PembayaranAlokasi::query()
            ->select('siswa_idsiswa', DB::raw('SUM(nominal) as reversal'))
            ->whereIn('jenis', ['reversal-refund', 'reversal-koreksi'])
            ->whereNull('tagihan_idtagihan')
            ->groupBy('siswa_idsiswa');

        $masukMap = $masuk->pluck('masuk', 'siswa_idsiswa');
        $dipakaiMap = $dipakai->pluck('dipakai', 'siswa_idsiswa');
        $reversalMap = $reversal->pluck('reversal', 'siswa_idsiswa');

        $semua = $masukMap->keys()->merge($dipakaiMap->keys())->merge($reversalMap->keys())->unique()->values()->all();

        $temuan = [];
        $totalSemua = 0;
        foreach ($semua as $siswaId) {
            $m = (int) ($masukMap[$siswaId] ?? 0);
            $d = (int) ($dipakaiMap[$siswaId] ?? 0);
            $r = (int) ($reversalMap[$siswaId] ?? 0);
            $saldo = $m - $d - $r;

            if ($saldo < 0) {
                $totalSemua++;
                if (count($temuan) < self::BATAS_PER_KATEGORI) {
                    $temuan[] = [
                        'kategori' => self::KATEGORI_SALDO_NEGATIF,
                        'referensi_tipe' => 'siswa',
                        'referensi_id' => (int) $siswaId,
                        'siswa_idsiswa' => (int) $siswaId,
                        'tagihan_idtagihan' => null,
                        'nominal_selisih' => abs($saldo),
                        'keterangan' => "Saldo siswa negatif: masuk {$m}, dipakai {$d}, reversal {$r}, selisih {$saldo}.",
                        'status' => self::STATUS_KRTIS,
                    ];
                }
            }
        }

        return ['kategori' => self::KATEGORI_SALDO_NEGATIF, 'temuan' => $temuan, 'total' => $totalSemua, 'terpotong' => $totalSemua > self::BATAS_PER_KATEGORI];
    }

    // ------------------------------ 8. Kuitansi tanpa pembayaran valid / gagal

    private static function cekKuitansiTanpaPembayaranValid(?string $periode): array
    {
        $query = Kuitansi::query()->whereDoesntHave('pembayaran', function ($q) {
            $q->where('status', 'success');
        });

        $total = (int) $query->count();
        $rows = $query->orderBy('idkuitansi')->limit(self::BATAS_PER_KATEGORI)->get();

        $temuan = [];
        foreach ($rows as $k) {
            $temuan[] = [
                'kategori' => self::KATEGORI_KUITANSI_TANPA_PEMBAYARAN_VALID,
                'referensi_tipe' => 'kuitansi',
                'referensi_id' => $k->idkuitansi,
                'siswa_idsiswa' => $k->siswa_idsiswa,
                'tagihan_idtagihan' => $k->tagihan_idtagihan,
                'nominal_selisih' => null,
                'keterangan' => "Kuitansi {$k->nomor} tidak terkait pembayaran berstatus success.",
                'status' => self::STATUS_KRTIS,
            ];
        }

        return ['kategori' => self::KATEGORI_KUITANSI_TANPA_PEMBAYARAN_VALID, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // ------------------------- 9. Kuitansi tidak cocok dengan pembayaran/tagihan/siswa

    private static function cekKuitansiDataTidakCocok(?string $periode): array
    {
        $rows = Kuitansi::query()->with('pembayaran')
            ->orderByDesc('idkuitansi')
            ->limit(self::BATAS_PER_KATEGORI)
            ->get();

        $temuan = [];
        $totalSemua = 0;
        foreach ($rows as $k) {
            $p = $k->pembayaran;
            if (! $p) {
                continue;
            }

            $issues = [];
            if ((int) $k->siswa_idsiswa !== (int) $p->siswa_idsiswa) {
                $issues[] = 'siswa berbeda';
            }
            if ((int) $k->tagihan_idtagihan !== (int) $p->tagihan_idtagihan) {
                $issues[] = 'tagihan berbeda';
            }

            $snapNominal = (int) ($k->snapshot_kuitansi['nominal'] ?? 0);
            if ($snapNominal !== (int) $p->nominal_dibayar) {
                $issues[] = 'nominal snapshot berbeda';
            }

            if ($issues !== []) {
                $totalSemua++;
                $temuan[] = [
                    'kategori' => self::KATEGORI_KUITANSI_DATA_TIDAK_COCOK,
                    'referensi_tipe' => 'kuitansi',
                    'referensi_id' => $k->idkuitansi,
                    'siswa_idsiswa' => $k->siswa_idsiswa,
                    'tagihan_idtagihan' => $k->tagihan_idtagihan,
                    'nominal_selisih' => abs($snapNominal - (int) $p->nominal_dibayar),
                    'keterangan' => "Kuitansi {$k->nomor} tidak cocok dengan pembayaran: ".implode(', ', $issues).'.',
                    'status' => self::STATUS_TINJAU,
                ];
            }
        }

        // Iterasi tidak dibatasi per temuan (hanya pada baris kuitansi), truncated ditentukan bila baris yang diiterasi mencapai batas
        $jumlahKuitansi = Kuitansi::count();

        return ['kategori' => self::KATEGORI_KUITANSI_DATA_TIDAK_COCOK, 'temuan' => $temuan, 'total' => $totalSemua, 'terpotong' => $jumlahKuitansi > self::BATAS_PER_KATEGORI];
    }

    // --------------------------------------------- 10. Pembayaran terverifikasi tanpa kuitansi

    private static function cekSuccessTanpaKuitansi(?string $periode): array
    {
        // Scope manual: jalur online belum menghasilkan kuitansi otomatis (lihat batasan Sprint 5/6)
        $query = Pembayaran::query()
            ->where('status', 'success')
            ->where('provider', 'manual')
            ->whereDoesntHave('kuitansi');
        $query = self::basisPeriodeTanggal($query, 'dibayar_pada', $periode);

        $total = (int) $query->count();
        $rows = $query->orderBy('idpembayaran')->limit(self::BATAS_PER_KATEGORI)
            ->get(['idpembayaran', 'siswa_idsiswa', 'tagihan_idtagihan', 'nominal_dibayar']);

        $temuan = [];
        foreach ($rows as $p) {
            $temuan[] = [
                'kategori' => self::KATEGORI_SUCCESS_TANPA_KUITANSI,
                'referensi_tipe' => 'pembayaran',
                'referensi_id' => $p->idpembayaran,
                'siswa_idsiswa' => $p->siswa_idsiswa,
                'tagihan_idtagihan' => $p->tagihan_idtagihan,
                'nominal_selisih' => (int) $p->nominal_dibayar,
                'keterangan' => 'Pembayaran manual terverifikasi tanpa kuitansi.',
                'status' => self::STATUS_TINJAU,
            ];
        }

        return ['kategori' => self::KATEGORI_SUCCESS_TANPA_KUITANSI, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // ------------------ 11. Status pembayaran manual tidak konsisten dengan histori verifikasi

    private static function cekManualVerifikasiTidakKonsisten(?string $periode): array
    {
        $query = Pembayaran::query()->where('provider', 'manual');
        $query = self::basisPeriodeTanggal($query, 'tanggal_pembayaran', $periode);

        $rows = $query->orderBy('idpembayaran')->limit(self::BATAS_PER_KATEGORI)
            ->get(['idpembayaran', 'siswa_idsiswa', 'tagihan_idtagihan', 'status', 'diverifikasi_oleh', 'diverifikasi_pada', 'alasan_gagal', 'alasan_penolakan']);

        $temuan = [];
        $totalSemua = 0;
        foreach ($rows as $p) {
            $issues = [];

            if ($p->status === 'success' && (! $p->diverifikasi_oleh || ! $p->diverifikasi_pada)) {
                $issues[] = 'status success tanpa verifikator/waktu verifikasi';
            }

            if ($p->status === 'pending' && $p->diverifikasi_pada) {
                $issues[] = 'status pending tetapi sudah ada timestamp verifikasi';
            }

            if ($p->status === 'failed' && ! $p->alasan_gagal && ! $p->alasan_penolakan) {
                $issues[] = 'status gagal/ditolak tanpa alasan';
            }

            if ($issues !== []) {
                $totalSemua++;
                $temuan[] = [
                    'kategori' => self::KATEGORI_MANUAL_VERIFIKASI_TIDAK_KONSISTEN,
                    'referensi_tipe' => 'pembayaran',
                    'referensi_id' => $p->idpembayaran,
                    'siswa_idsiswa' => $p->siswa_idsiswa,
                    'tagihan_idtagihan' => $p->tagihan_idtagihan,
                    'nominal_selisih' => null,
                    'keterangan' => 'Pembayaran manual: '.implode(', ', $issues).'.',
                    'status' => self::STATUS_TINJAU,
                ];
            }
        }

        return ['kategori' => self::KATEGORI_MANUAL_VERIFIKASI_TIDAK_KONSISTEN, 'temuan' => $temuan, 'total' => $totalSemua, 'terpotong' => $totalSemua >= self::BATAS_PER_KATEGORI || $rows->count() >= self::BATAS_PER_KATEGORI];
    }

    // ------------------ 12. Reversal (refund/koreksi) tanpa referensi pengajuan

    private static function cekReversalTanpaReferensi(?string $periode): array
    {
        $query = PembayaranAlokasi::query()
            ->whereIn('jenis', ['reversal-refund', 'reversal-koreksi'])
            ->where(function ($q) {
                $q->whereNull('referensi_tipe')->orWhereNull('referensi_id');
            });
        $query = self::basisPeriodeTanggal($query, 'created_at', $periode);

        $total = (int) $query->count();
        $rows = $query->orderBy('idpembayaran_alokasi')->limit(self::BATAS_PER_KATEGORI)
            ->get(['idpembayaran_alokasi', 'pembayaran_idpembayaran', 'siswa_idsiswa', 'tagihan_idtagihan', 'nominal', 'jenis']);

        $temuan = [];
        foreach ($rows as $r) {
            $temuan[] = [
                'kategori' => self::KATEGORI_REVERSAL_TANPA_REFERENSI,
                'referensi_tipe' => 'alokasi',
                'referensi_id' => $r->idpembayaran_alokasi,
                'siswa_idsiswa' => $r->siswa_idsiswa,
                'tagihan_idtagihan' => $r->tagihan_idtagihan,
                'nominal_selisih' => (int) $r->nominal,
                'keterangan' => "Alokasi {$r->jenis} (pembayaran #{$r->pembayaran_idpembayaran}) tidak memiliki referensi pengajuan refund/koreksi.",
                'status' => self::STATUS_TINJAU,
            ];
        }

        return ['kategori' => self::KATEGORI_REVERSAL_TANPA_REFERENSI, 'temuan' => $temuan, 'total' => $total, 'terpotong' => $total > self::BATAS_PER_KATEGORI];
    }

    // ------------------ 13. Reversal melebihi alokasi asli per pembayaran per sumber dana

    private static function cekReversalMelebihiAlokasi(?string $periode): array
    {
        $grouped = DB::table('pembayaran_alokasi')
            ->select('pembayaran_idpembayaran',
                DB::raw("SUM(CASE WHEN jenis = 'tagihan' THEN nominal ELSE 0 END) as alokasi_tagihan"),
                DB::raw("SUM(CASE WHEN jenis = 'saldo' THEN nominal ELSE 0 END) as alokasi_saldo"),
                DB::raw("SUM(CASE WHEN jenis IN ('reversal-refund','reversal-koreksi') AND tagihan_idtagihan IS NOT NULL THEN nominal ELSE 0 END) as reversal_tagihan"),
                DB::raw("SUM(CASE WHEN jenis IN ('reversal-refund','reversal-koreksi') AND tagihan_idtagihan IS NULL THEN nominal ELSE 0 END) as reversal_saldo"))
            ->groupBy('pembayaran_idpembayaran');

        $bermasalah = DB::query()->fromSub($grouped, 'g')
            ->whereRaw('(g.reversal_tagihan > g.alokasi_tagihan) OR (g.reversal_saldo > g.alokasi_saldo)')
            ->get();

        $totalSemua = $bermasalah->count();
        $rows = $bermasalah->take(self::BATAS_PER_KATEGORI);

        $temuan = [];
        foreach ($rows as $row) {
            $pembayaran = Pembayaran::find($row->pembayaran_idpembayaran);
            $masalah = [];
            if ($row->reversal_tagihan > $row->alokasi_tagihan) {
                $masalah[] = "reversal tagihan {$row->reversal_tagihan} > alokasi tagihan {$row->alokasi_tagihan}";
            }
            if ($row->reversal_saldo > $row->alokasi_saldo) {
                $masalah[] = "reversal saldo {$row->reversal_saldo} > alokasi saldo {$row->alokasi_saldo}";
            }
            $temuan[] = [
                'kategori' => self::KATEGORI_REVERSAL_MELEBIHI_ALOKASI,
                'referensi_tipe' => 'pembayaran',
                'referensi_id' => (int) $row->pembayaran_idpembayaran,
                'siswa_idsiswa' => $pembayaran?->siswa_idsiswa,
                'tagihan_idtagihan' => $pembayaran?->tagihan_idtagihan,
                'nominal_selisih' => max(0, (int) $row->reversal_tagihan - (int) $row->alokasi_tagihan, (int) $row->reversal_saldo - (int) $row->alokasi_saldo),
                'keterangan' => 'Reversal melebihi alokasi asli: '.implode('; ', $masalah).'.',
                'status' => self::STATUS_KRTIS,
            ];
        }

        return ['kategori' => self::KATEGORI_REVERSAL_MELEBIHI_ALOKASI, 'temuan' => $temuan, 'total' => $totalSemua, 'terpotong' => $totalSemua > self::BATAS_PER_KATEGORI];
    }

    /**
     * Ringkasan singkat untuk keperluan ekspor/rambu: jumlah ditemukan, tersimpan, terpotong.
     */
    public static function ringkasanExport(RekonsiliasiHasil $hasil): array
    {
        $ringkasan = self::normalisasiRingkasan($hasil->ringkasan ?? null);

        return [
            'total_ditemukan' => (int) ($ringkasan['total_ditemukan'] ?? $hasil->jumlah_temuan),
            'total_tersimpan' => (int) ($ringkasan['total_tersimpan'] ?? $hasil->jumlah_temuan),
            'per_kategori' => self::perKategoriPada($hasil),
            'terpotong' => self::terpotongPada($hasil),
        ];
    }
}
