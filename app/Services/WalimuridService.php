<?php

namespace App\Services;

use App\Models\User;
use App\Models\Walimurid;
use App\Models\WalimuridHistory;
use App\Models\WalimuridPermintaanPutus;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * WALIMURID SERVICE — Tolak Ukur Fondasi Hubungan Wali–Siswa (2B.7.4B)
 *
 * Berdasarkan keputusan resmi 2B.7.4A (lihat LAPORAN_AUDIT_DESAIN_HUBUNGAN_WALI_SISWA_2B_7_4A.md):
 *   KF-1  wali boleh mencari siswa via NIS/nama, hasil relevan (bukan daftar semua siswa)
 *   KF-2  siswa wajib status='aktif'
 *   KF-3  hanya akun wali yang mengajukan; admin TIDAK boleh mengajukan atas nama wali
 *
 * Tanggung jawab:
 *   - query relasi hanya milik akun wali;
 *   - guard akun wali (level + status) dan guard kewenangan admin (W1–W4);
 *   - pengajuan/ajukan ulang dalam transaksi dengan histori append-only yang sama;
 *   - keputusan pengajuan (P3 verifikasi / P4 penolakan) ber-lock, transaksional, berhistori;
 *   - menangkap duplicate key (1062) → DomainException berbahasa Indonesia.
 *
 * TIDAK termasuk: UI, route, menu, akses akademik, AksesPeriodeService (T1d).
 */
class WalimuridService
{
    public const MENU_VERIFIKASI = 'wali-antrian-verifikasi';

    public const AKTOR_PERAN = ['superadmin', 'admin', 'wali', 'sistem'];

    // ------------------------------------------------------------------
    // Kewenangan & guard
    // ------------------------------------------------------------------

    /** Aktor harus wali aktif; level & status diverifikasi server-side. */
    public static function pastikanWaliAktif(User $aktor): void
    {
        abort_unless($aktor->level === 'wali', 403, 'Hanya akun wali yang dapat melakukan tindakan ini.');
        abort_unless($aktor->status_akun === 'aktif', 403, 'Akun wali tidak aktif.');
    }

    /**
     * W1: level ∈ {superadmin, admin}; W2: punya usermenu menu verifikasi;
     * W3: bukan pemilik relasi & bukan pengaju (bila $relasi diisi);
     * W4: untuk permintaan putus, bukan peminta (lihat `pastikanBerwenangPemutusan`).
     */
    public static function pastikanBerwenangVerifikasi(User $aktor, ?Walimurid $relasi = null): void
    {
        abort_unless(in_array($aktor->level, ['superadmin', 'admin'], true), 403, 'Hanya superadmin dan admin sekolah yang berwenang.');

        $punyaMenu = $aktor->usermenu()
            ->whereHas('appmenunew', fn ($query) => $query->where('url', self::MENU_VERIFIKASI))
            ->exists();

        abort_unless($punyaMenu, 403, 'Akun ini tidak memiliki izin menu antrian verifikasi.');

        if ($relasi !== null) {
            abort_if((int) $relasi->user_iduser === (int) $aktor->id, 403, 'Tidak boleh mengakses relasi milik sendiri.');
            abort_if((int) $relasi->pengaju_id === (int) $aktor->id, 403, 'Tidak boleh memutuskan pengajuan sendiri.');
        }
    }

    /** W4 untuk P5b: aktor pengelola tidak boleh memutuskan permintaan yang ia ajukan sendiri. */
    public static function pastikanBerwenangPemutusan(User $aktor, WalimuridPermintaanPutus $permintaan): void
    {
        self::pastikanBerwenangVerifikasi($aktor);
        abort_if((int) $permintaan->diminta_oleh === (int) $aktor->id, 403, 'Tidak boleh memutuskan permintaan sendiri.');
    }

    // ------------------------------------------------------------------
    // Query terscope (IDOR-safe)
    // ------------------------------------------------------------------

    /** Relasi milik akun wali, hanya bila aktif & wali. */
    public static function relasiUntukWali(User $wali)
    {
        self::pastikanWaliAktif($wali);

        return Walimurid::query()
            ->with('siswa')
            ->where('user_iduser', $wali->id)
            ->orderByDesc('idwalimurid');
    }

    /** Relasi tunggal milik wali (untuk halaman detail). */
    public static function relasiMilikWali(User $wali, int $relasiId)
    {
        self::pastikanWaliAktif($wali);

        return Walimurid::query()
            ->with('siswa', 'histories')
            ->where('user_iduser', $wali->id)
            ->whereKey($relasiId)
            ->firstOrFail();
    }

    /** Cari siswa relevan (KF-1): NIS/NISN/nama, hanya siswa aktif, terbatas. */
    public static function cariSiswaRelevan(User $wali, string $kata, int $limit = 20)
    {
        self::pastikanWaliAktif($wali);

        $kata = trim($kata);
        if ($kata === '') {
            return collect();
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $kata).'%';

        return \App\Models\Siswa::query()
            ->where('status', 'aktif')
            ->where(function ($w) use ($like) {
                $w->where('nis', 'like', $like)
                    ->orWhere('nisn', 'like', $like)
                    ->orWhere('nama', 'like', $like);
            })
            ->select('idsiswa', 'nis', 'nisn', 'nama')
            ->orderBy('nama')
            ->limit(min(max($limit, 1), 50))
            ->get();
    }

    // ------------------------------------------------------------------
    // Pengajuan & pengajuan ulang
    // ------------------------------------------------------------------

    /**
     * Pengajuan relasi oleh akun wali. Admin tidak boleh mengajukan atas nama wali (KF-3).
     *
     * @return Walimurid
     */
    public static function ajukanRelasi(User $wali, int $siswaId, string $hubungan, ?string $alasan = null)
    {
        self::pastikanWaliAktif($wali);

        if (! in_array($hubungan, Walimurid::HUBUNGAN, true)) {
            throw new \InvalidArgumentException('Jenis hubungan tidak valid.');
        }
        if ($alasan !== null && mb_strlen($alasan) > 500) {
            throw new \InvalidArgumentException('Alasan maksimal 500 karakter.');
        }

        return DB::transaction(function () use ($wali, $siswaId, $hubungan, $alasan) {
            $siswa = \App\Models\Siswa::query()->find($siswaId);

            if (! $siswa) {
                throw new \DomainException('Siswa tidak ditemukan.');
            }
            if ($siswa->status !== 'aktif') {
                throw new \DomainException('Pengajuan hanya untuk siswa aktif.');
            }

            try {
                $relasi = Walimurid::create([
                    'user_iduser' => $wali->id,
                    'siswa_idsiswa' => $siswa->idsiswa,
                    'hubungan' => $hubungan,
                    'status_hubungan' => 'pending',
                    'tanggal_mulai' => today(),
                    'tanggal_verifikasi' => null,
                    'pengaju_id' => $wali->id,
                    'verifikator_id' => null,
                    'catatan' => $alasan,
                ]);
            } catch (QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? null) === 1062) {
                    throw new \DomainException('Pengajuan untuk siswa ini sudah ada dan sedang diproses.');
                }
                throw $e;
            }

            self::tulisHistoriRelasi($relasi, $wali, 'wali', 'pengajuan', null, 'pending', $alasan);

            return $relasi;
        });
    }

    /** Pengajuan ulang dari relasi `rejected`/`terminated` → baris baru (bukan ubah histori lama). */
    public static function ajukanUlangRelasi(User $wali, int $relasiAsalId, string $hubungan, ?string $alasan = null)
    {
        self::pastikanWaliAktif($wali);

        if (! in_array($hubungan, Walimurid::HUBUNGAN, true)) {
            throw new \InvalidArgumentException('Jenis hubungan tidak valid.');
        }

        return DB::transaction(function () use ($wali, $relasiAsalId, $hubungan, $alasan) {
            // Hanya relasi milik sendiri — IDOR
            $asal = Walimurid::query()
                ->where('user_iduser', $wali->id)
                ->whereKey($relasiAsalId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($asal->status_hubungan, ['rejected', 'terminated'], true)) {
                throw new \DomainException('Pengajuan ulang hanya dari relasi yang ditolak atau diakhiri.');
            }

            $siswa = \App\Models\Siswa::query()->find($asal->siswa_idsiswa);

            if (! $siswa || $siswa->status !== 'aktif') {
                throw new \DomainException('Pengajuan hanya untuk siswa aktif.');
            }

            try {
                $relasi = Walimurid::create([
                    'user_iduser' => $wali->id,
                    'siswa_idsiswa' => $asal->siswa_idsiswa,
                    'hubungan' => $hubungan,
                    'status_hubungan' => 'pending',
                    'tanggal_mulai' => today(),
                    'tanggal_verifikasi' => null,
                    'pengaju_id' => $wali->id,
                    'verifikator_id' => null,
                    'catatan' => $alasan ?: ('Pengajuan ulang dari relasi #'.$asal->idwalimurid),
                ]);
            } catch (QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? null) === 1062) {
                    throw new \DomainException('Pengajuan untuk siswa ini sudah ada dan sedang diproses.');
                }
                throw $e;
            }

            $catatan = $alasan ?: ('Pengajuan ulang dari relasi #'.$asal->idwalimurid);
            self::tulisHistoriRelasi($relasi, $wali, 'wali', 'pengajuan_ulang', null, 'pending', $catatan);

            return $relasi;
        });
    }

    // ------------------------------------------------------------------
    // Histori (append-only, di dalam transaksi yang sama)
    // ------------------------------------------------------------------

    public static function tulisHistoriRelasi(
        Walimurid $relasi,
        User $aktor,
        string $aktorPeran,
        string $event,
        ?string $statusSebelum,
        string $statusSesudah,
        ?string $alasan = null,
        ?string $tanggalMulaiSebelum = null,
        ?string $tanggalMulaiSesudah = null,
        ?string $tanggalBerhentiSebelum = null,
        ?string $tanggalBerhentiSesudah = null,
    ): WalimuridHistory {
        if (! in_array($aktorPeran, WalimuridHistory::AKTOR_PERAN, true)) {
            throw new \InvalidArgumentException('aktor_peran tidak valid.');
        }
        if (! in_array($event, WalimuridHistory::EVENT, true)) {
            throw new \InvalidArgumentException('event tidak valid.');
        }
        if ($statusSesudah === null || ! in_array($statusSesudah, Walimurid::STATUS_RELATION, true)) {
            throw new \InvalidArgumentException('status_sesudah tidak valid.');
        }

        return WalimuridHistory::create([
            'walimurid_id' => $relasi->idwalimurid,
            'user_iduser' => $relasi->user_iduser,
            'siswa_idsiswa' => $relasi->siswa_idsiswa,
            'aktor_id' => $aktor->id,
            'aktor_peran' => $aktorPeran,
            'event' => $event,
            'status_sebelum' => $statusSebelum,
            'status_sesudah' => $statusSesudah,
            'tanggal_mulai_sebelum' => $tanggalMulaiSebelum,
            'tanggal_mulai_sesudah' => $tanggalMulaiSesudah,
            'tanggal_berhenti_sebelum' => $tanggalBerhentiSebelum,
            'tanggal_berhenti_sesudah' => $tanggalBerhentiSesudah,
            'alasan' => $alasan !== null ? mb_substr(trim($alasan), 0, 500) : null,
            'created_at' => now(),
        ]);
    }

    private static function peranAktor(User $aktor): string
    {
        return match ($aktor->level) {
            'superadmin' => 'superadmin',
            'admin' => 'admin',
            default => 'sistem',
        };
    }

    // ------------------------------------------------------------------
    // Keputusan (P3 — verifikasi, P4 — penolakan)
    // Desain Revisi 4 §8.3/§8.4. Hanya pengelola berwenang (W1–W4) & hanya `pending`.
    // ------------------------------------------------------------------

    /**
     * Verifikasi pengajuan relasi (P3).
     *
     * Perubahan (`walimurid` + histori) dalam SATU transaksi DB + lockForUpdate,
     * sehingga dua pengelola yang memutuskan bersamaan tidak menghasilkan keputusan ganda:
     * salah satu akan mendapatkan 0 baris yang cocok untuk update → DomainException.
     *
     * @param  string  $tanggalMulai  Format `Y-m-d`; wajib <= hari ini dan <= tanggal_verifikasi (T1d).
     *                                Bila berbeda dari `tanggal_mulai` usulan pengajuan, `$alasanPeriode` wajib diisi.
     * @param  string|null  $alasanPeriode  Wajib bila tanggal_mulai berbeda dari usulan.
     * @param  string|null  $catatan  Catatan keputusan (opsional).
     */
    public static function verifikasiRelasi(User $aktor, int $relasiId, string $tanggalMulai, ?string $alasanPeriode = null, ?string $catatan = null): Walimurid
    {
        // ---- Validasi format & batas tanggal SEBELUM transaksi ----
        try {
            $awal = \Carbon\Carbon::parse($tanggalMulai)->startOfDay();
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Format tanggal_mulai tidak valid.');
        }

        $sekarang = now();
        if ($awal->greaterThan($sekarang->copy()->startOfDay())) {
            throw new \InvalidArgumentException('tanggal_mulai tidak boleh melewati tanggal_verifikasi (T1d).');
        }

        return DB::transaction(function () use ($aktor, $relasiId, $awal, $alasanPeriode, $catatan, $sekarang) {
            // ---- Kunci baris; hanya dengan ID; cegah TOCTOU ----
            $relasi = Walimurid::query()->lockForUpdate()->findOrFail($relasiId);

            // ---- W1–W3: level + menu + bukan pemilik/pengaju ----
            self::pastikanBerwenangVerifikasi($aktor, $relasi);

            // ---- Hanya `pending` yang boleh diputuskan ----
            if ($relasi->status_hubungan !== 'pending') {
                throw new \DomainException('Pengajuan ini sudah diputuskan. Hanya pengajuan pending yang dapat diverifikasi atau ditolak.');
            }

            // ---- T1d: tanggal_mulai tidak boleh setelah tanggal_verifikasi ----
            if ($awal->greaterThan($sekarang->copy()->startOfDay())) {
                throw new \InvalidArgumentException('tanggal_mulai tidak boleh melewati tanggal_verifikasi (T1d).');
            }

            $statusSebelum = $relasi->status_hubungan;
            $berubahPeriode = ! $relasi->tanggal_mulai->isSameDay($awal);
            $mulaiSebelum = $relasi->tanggal_mulai?->toDateString();

            $relasi->status_hubungan = 'verified';
            $relasi->verifikator_id = $aktor->id;
            $relasi->tanggal_verifikasi = $sekarang;
            $relasi->tanggal_mulai = $awal;
            if ($catatan !== null) {
                $relasi->catatan = $catatan;
            }
            $relasi->save();

            // Histori verifikasi (wajib, transaksi sama)
            self::tulisHistoriRelasi(
                $relasi,
                $aktor,
                self::peranAktor($aktor),
                'verifikasi',
                $statusSebelum,
                'verified',
                $catatan,
            );

            // Histori perubahan periode bila berbeda dari usulan
            if ($berubahPeriode) {
                if (trim((string) $alasanPeriode) === '') {
                    throw new \InvalidArgumentException('Alasan perubahan tanggal akses wajib diisi.');
                }

                self::tulisHistoriRelasi(
                    $relasi,
                    $aktor,
                    self::peranAktor($aktor),
                    'periode_diubah',
                    'verified',
                    'verified',
                    $alasanPeriode,
                    $mulaiSebelum,
                    $awal->toDateString(),
                    null,
                    $relasi->tanggal_berhenti?->toDateString(),
                );
            }

            return $relasi->fresh();
        });
    }

    /**
     * Penolakan pengajuan relasi (P4).
     *
     * @param  string  $alasan  wajib; tersimpan pada `catatan` dan histori `penolakan`.
     */
    public static function tolakRelasi(User $aktor, int $relasiId, string $alasan): Walimurid
    {
        $alasan = trim($alasan);

        if ($alasan === '') {
            throw new \InvalidArgumentException('Alasan penolakan wajib diisi.');
        }
        if (mb_strlen($alasan) > 500) {
            throw new \InvalidArgumentException('Alasan penolakan maksimal 500 karakter.');
        }

        return DB::transaction(function () use ($aktor, $relasiId, $alasan) {
            // ---- Kunci baris ----
            $relasi = Walimurid::query()->lockForUpdate()->findOrFail($relasiId);

            // ---- W1–W3 ----
            self::pastikanBerwenangVerifikasi($aktor, $relasi);

            // ---- Hanya `pending` ----
            if ($relasi->status_hubungan !== 'pending') {
                throw new \DomainException('Pengajuan ini sudah diputuskan. Hanya pengajuan pending yang dapat diverifikasi atau ditolak.');
            }

            $statusSebelum = $relasi->status_hubungan;

            $relasi->status_hubungan = 'rejected';
            $relasi->verifikator_id = $aktor->id;
            $relasi->tanggal_berhenti = today();
            $relasi->catatan = $alasan;
            $relasi->save(); // tanggal_verifikasi TIDAK diisi (sesuai desain §8.4)

            self::tulisHistoriRelasi(
                $relasi,
                $aktor,
                self::peranAktor($aktor),
                'penolakan',
                $statusSebelum,
                'rejected',
                $alasan,
            );

            return $relasi->fresh();
        });
    }

    /**
     * Antrean pengajuan relasi berstatus pending (untuk pengelola).
     * IDOR-safe: hanya data yang diperlukan untuk keputusan; filter/search opsional.
     */
    public static function antreanPending(?string $search = null)
    {
        $query = Walimurid::query()
            ->with(['siswa', 'pemilik'])
            ->where('status_hubungan', 'pending')
            ->orderBy('idwalimurid');

        if ($search !== null && trim($search) !== '') {
            $kata = trim($search);
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $kata).'%';
            $query->where(function ($w) use ($like) {
                $w->whereHas('siswa', function ($s) use ($like) {
                    $s->where('nama', 'like', $like)->orWhere('nis', 'like', $like);
                })->orWhereHas('pemilik', function ($u) use ($like) {
                    $u->where('name', 'like', $like)->orWhere('username', 'like', $like);
                });
            });
        }

        return $query;
    }

    /**
     * Daftar relasi aktif (verified) yang dapat diputus langsung oleh pengelola (P5a).
     */
    public static function relasiAktif(?string $search = null)
    {
        $query = Walimurid::query()
            ->with(['siswa', 'pemilik'])
            ->where('status_hubungan', 'verified')
            ->orderByDesc('idwalimurid');

        if ($search !== null && trim($search) !== '') {
            $kata = trim($search);
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $kata).'%';
            $query->where(function ($w) use ($like) {
                $w->whereHas('siswa', function ($s) use ($like) {
                    $s->where('nama', 'like', $like)->orWhere('nis', 'like', $like);
                })->orWhereHas('pemilik', function ($u) use ($like) {
                    $u->where('name', 'like', $like)->orWhere('username', 'like', $like);
                });
            });
        }

        return $query;
    }

    /**
     * P5a - Pemutusan relasi aktif langsung oleh pengelola berwenang.
     * Alasan wajib; histori append-only ditulis dalam transaksi yang sama.
     */
    public static function putusRelasiLangsung(User $aktor, int $relasiId, string $alasan): Walimurid
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new \InvalidArgumentException('Alasan pemutusan wajib diisi.');
        }
        if (mb_strlen($alasan) > 500) {
            throw new \InvalidArgumentException('Alasan pemutusan maksimal 500 karakter.');
        }

        return DB::transaction(function () use ($aktor, $relasiId, $alasan) {
            $relasi = Walimurid::query()->lockForUpdate()->findOrFail($relasiId);

            self::pastikanBerwenangVerifikasi($aktor, $relasi);

            if ($relasi->status_hubungan !== 'verified') {
                throw new \DomainException('Hanya relasi aktif (verified) yang dapat diputus langsung.');
            }

            $statusSebelum = $relasi->status_hubungan;
            $berhentiSebelum = $relasi->tanggal_berhenti?->toDateString();

            $relasi->status_hubungan = 'terminated';
            $relasi->verifikator_id = $aktor->id;
            $relasi->tanggal_berhenti = today();
            $relasi->catatan = $alasan;
            $relasi->save();

            self::tulisHistoriRelasi(
                $relasi,
                $aktor,
                self::peranAktor($aktor),
                'pemutusan',
                $statusSebelum,
                'terminated',
                $alasan,
                $relasi->tanggal_mulai?->toDateString(),
                $relasi->tanggal_mulai?->toDateString(),
                $berhentiSebelum,
                today()->toDateString()
            );

            return $relasi->fresh();
        });
    }

    /** P5b - Permintaan pemutusan relasi oleh wali (menunggu keputusan pengelola). */
    public static function buatPermintaanPutus(User $wali, int $relasiId, string $alasan): WalimuridPermintaanPutus
    {
        self::pastikanWaliAktif($wali);
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new \InvalidArgumentException('Alasan permintaan pemutusan wajib diisi.');
        }
        if (mb_strlen($alasan) > 500) {
            throw new \InvalidArgumentException('Alasan maksimal 500 karakter.');
        }

        return DB::transaction(function () use ($wali, $relasiId, $alasan) {
            $relasi = Walimurid::query()->lockForUpdate()->findOrFail($relasiId);

            if ((int) $relasi->user_iduser !== (int) $wali->id) {
                abort(403, 'Relasi bukan milik Anda.');
            }
            if ($relasi->status_hubungan !== 'verified') {
                throw new \DomainException('Hanya relasi aktif yang dapat dimintakan pemutusan.');
            }

            try {
                $permintaan = WalimuridPermintaanPutus::create([
                    'walimurid_id' => $relasi->idwalimurid,
                    'diminta_oleh' => $wali->id,
                    'alasan' => $alasan,
                    'status_permintaan' => 'menunggu',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? null) === 1062) {
                    throw new \DomainException('Sudah ada permintaan pemutusan yang menunggu untuk relasi ini.');
                }
                throw $e;
            }

            self::tulisHistoriRelasi(
                $relasi, $wali, 'wali', 'permintaan_putus',
                'verified', 'verified', $alasan
            );

            return $permintaan;
        });
    }

    public static function permintaanPutusMenunggu()
    {
        return WalimuridPermintaanPutus::query()
            ->with(['relasi.siswa', 'relasi.pemilik', 'peminta'])
            ->where('status_permintaan', 'menunggu')
            ->orderBy('idpermintaan_putus');
    }

    public static function setujuiPermintaanPutus(User $aktor, int $permintaanId, ?string $catatan = null): Walimurid
    {
        return DB::transaction(function () use ($aktor, $permintaanId, $catatan) {
            $permintaan = WalimuridPermintaanPutus::query()->lockForUpdate()->findOrFail($permintaanId);
            self::pastikanBerwenangPemutusan($aktor, $permintaan);

            if ($permintaan->status_permintaan !== 'menunggu') {
                throw new \DomainException('Permintaan ini sudah diputuskan.');
            }

            $relasi = Walimurid::query()->lockForUpdate()->findOrFail($permintaan->walimurid_id);
            if ($relasi->status_hubungan !== 'verified') {
                throw new \DomainException('Relasi tidak lagi aktif.');
            }

            $statusSebelum = $relasi->status_hubungan;
            $berhentiSebelum = $relasi->tanggal_berhenti?->toDateString();

            $relasi->status_hubungan = 'terminated';
            $relasi->verifikator_id = $aktor->id;
            $relasi->tanggal_berhenti = today();
            $relasi->catatan = $permintaan->alasan;
            $relasi->save();

            $permintaan->status_permintaan = 'disetujui';
            $permintaan->diputuskan_oleh = $aktor->id;
            $permintaan->diputuskan_pada = now();
            $permintaan->catatan_keputusan = $catatan;
            $permintaan->save();

            self::tulisHistoriRelasi(
                $relasi, $aktor, self::peranAktor($aktor), 'pemutusan',
                $statusSebelum, 'terminated', $permintaan->alasan,
                $relasi->tanggal_mulai?->toDateString(), $relasi->tanggal_mulai?->toDateString(),
                $berhentiSebelum, today()->toDateString()
            );

            return $relasi->fresh();
        });
    }

    public static function tolakPermintaanPutus(User $aktor, int $permintaanId, string $catatan): void
    {
        $catatan = trim($catatan);
        if ($catatan === '') {
            throw new \InvalidArgumentException('Catatan penolakan wajib diisi.');
        }

        DB::transaction(function () use ($aktor, $permintaanId, $catatan) {
            $permintaan = WalimuridPermintaanPutus::query()->lockForUpdate()->findOrFail($permintaanId);
            self::pastikanBerwenangPemutusan($aktor, $permintaan);

            if ($permintaan->status_permintaan !== 'menunggu') {
                throw new \DomainException('Permintaan ini sudah diputuskan.');
            }

            $permintaan->status_permintaan = 'ditolak';
            $permintaan->diputuskan_oleh = $aktor->id;
            $permintaan->diputuskan_pada = now();
            $permintaan->catatan_keputusan = $catatan;
            $permintaan->save();

            $relasi = Walimurid::query()->findOrFail($permintaan->walimurid_id);
            self::tulisHistoriRelasi(
                $relasi, $aktor, self::peranAktor($aktor), 'permintaan_putus_ditolak',
                'verified', 'verified', $catatan
            );
        });
    }

    /**
     * Relasi wali yang memenuhi syarat penerima notifikasi (2B.12, D3/D5/D7):
     * - verified, tanggal_mulai sudah berlaku, tanggal_berhenti null / masih berlaku (inklusif hari ini),
     * - akun wali level `wali` dan status_akun `aktif`.
     *
     * Satu query untuk banyak siswa; gunakan groupBy('siswa_idsiswa') oleh pemanggil.
     *
     * @param  array<int, int|string>  $siswaIds
     * @return \Illuminate\Support\Collection<int, Walimurid>
     */
    public static function relasiWaliAktifSiswa(array $siswaIds): \Illuminate\Support\Collection
    {
        $ids = collect($siswaIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        $today = now()->toDateString();

        return Walimurid::query()
            ->whereIn('siswa_idsiswa', $ids)
            ->where('status_hubungan', 'verified')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->where(fn ($query) => $query->whereNull('tanggal_berhenti')->orWhereDate('tanggal_berhenti', '>=', $today))
            ->whereHas('pemilik', fn ($query) => $query->where('level', 'wali')->where('status_akun', 'aktif'))
            ->get(['idwalimurid', 'user_iduser', 'siswa_idsiswa']);
    }

    /**
     * ID akun wali penerima notifikasi (unik) untuk kumpulan siswa.
     *
     * @param  array<int, int|string>  $siswaIds
     * @return array<int, int>
     */
    public static function penerimaWaliVerifiedAktif(array $siswaIds): array
    {
        return self::relasiWaliAktifSiswa($siswaIds)
            ->pluck('user_iduser')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Label kelas aktif (TA aktif) untuk banyak siswa; kosong bila konteks akademik invalid.
     *
     * @param  array<int, int|string>  $siswaIds
     * @return array<int, string> map siswa_idsiswa => "Nama Kelas[, Nama Kelas]"
     */
    public static function labelKelasSiswa(array $siswaIds): array
    {
        $ids = collect($siswaIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $konteks = KonteksAkademikService::ambil();
        if (! $konteks->valid() || ! $konteks->tahunAjaran) {
            return [];
        }

        $rows = \App\Models\Kelassiswa::query()
            ->with('kelas:idkelas,nama')
            ->whereIn('siswa_idsiswa', $ids)
            ->where('status', 'aktif')
            ->whereHas('kelas', fn ($query) => $query->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran))
            ->get(['siswa_idsiswa', 'kelas_idkelas']);

        $map = [];
        foreach ($rows as $row) {
            $nama = $row->kelas?->nama ?? '-';
            $sid = (int) $row->siswa_idsiswa;
            $map[$sid] = isset($map[$sid]) ? $map[$sid].', '.$nama : $nama;
        }

        return $map;
    }

    public static function pastikanAksesAkademikWali(User $wali, int $siswaId): Walimurid
    {
        self::pastikanWaliAktif($wali);

        $relasi = Walimurid::query()
            ->where('user_iduser', $wali->id)
            ->where('siswa_idsiswa', $siswaId)
            ->where('status_hubungan', 'verified')
            ->whereNull('tanggal_berhenti')
            ->first();

        if (! $relasi) {
            abort(403, 'Akses akademik tidak tersedia untuk siswa ini.');
        }

        // Kebijakan periode akses: fail-closed jika tanggal akademik tidak valid.
        $konteks = \App\Services\KonteksAkademikService::ambil();
        if (! $konteks->valid()) {
            abort(403, 'Konteks akademik tidak valid.');
        }
        if ($relasi->tanggal_mulai === null) {
            abort(403, 'Periode akses akademik tidak valid.');
        }
        if ($relasi->tanggal_mulai->greaterThan(now()->startOfDay())) {
            abort(403, 'Akses akademik belum dimulai.');
        }
        if ($relasi->tanggal_berhenti !== null && $relasi->tanggal_berhenti->lessThan(now()->startOfDay())) {
            abort(403, 'Akses akademik telah berakhir.');
        }

        return $relasi;
    }

    /**
     * Rekap kehadiran wali (read-only) untuk siswa terhubung.
     * Aturan penggabungan identik dengan RaporSiswaService::rekapKehadiran():
     * - absensi_harian menjadi sumber utama;
     * - absensi_perjam hanya MELENGKAPI tanggal tanpa absensi_harian;
     * - prioritas status: alpha > izin > sakit > hadir;
     * - dibatasi semester + tahun ajaran aktif dan periode relasi wali.
     *
     * @return array{hadir:int, sakit:int, izin:int, alpha:int, total:int, per_tanggal:\Illuminate\Support\Collection, persen:string}
     */
    /**
     * Baris detail rekap kehadiran (satu baris per tanggal) untuk ekspor CSV.
     *
     * Menggunakan kebijakan yang sama persis dengan rekapKehadiranWali():
     * - absensi harian sumber utama,
     * - absensi per jam hanya mengisi tanggal tanpa absensi harian,
     * - prioritas RaporSiswaService::PRIORITAS_STATUS (alpha > izin > sakit > hadir),
     * - dibatasi tahun ajaran aktif, semester aktif, kelas aktif siswa (kelassiswa),
     *   dan periode relasi wali yang berlaku.
     *
     * @return \Illuminate\Support\Collection<int, array{tanggal:string,status:string,sumber:string,keterangan:string}>
     */
    public static function detailRekapKehadiranWali(User $wali, int $siswaId): \Illuminate\Support\Collection
    {
        $relasi = self::pastikanAksesAkademikWali($wali, $siswaId);
        $konteks = KonteksAkademikService::ambil();

        $kosong = collect();

        if (! $konteks->valid() || ! $konteks->tahunAjaran || ! $konteks->semester) {
            return $kosong;
        }

        // ---- Kelas aktif siswa pada TA aktif (kelassiswa aktif) ----
        $kelasIds = \App\Models\Kelassiswa::query()
            ->where('siswa_idsiswa', $siswaId)
            ->where('status', 'aktif')
            ->whereHas('kelas', fn ($query) => $query->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran))
            ->pluck('kelas_idkelas')
            ->all();
        if ($kelasIds === []) {
            return $kosong;
        }

        // ---- Absensi harian: sumber utama, hanya kelas aktif ----
        $harianQuery = \App\Models\AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)
            ->where('semester_idsemester', $konteks->semester->idsemester)
            ->where('siswa_idsiswa', $siswaId)
            ->whereIn('kelas_idkelas', $kelasIds);
        if ($relasi->tanggal_mulai) {
            $harianQuery->whereDate('tanggal', '>=', $relasi->tanggal_mulai);
        }
        if ($relasi->tanggal_berhenti) {
            $harianQuery->whereDate('tanggal', '<=', $relasi->tanggal_berhenti);
        }

        /** @var \Illuminate\Support\Collection<string, \App\Models\AbsensiHarian> $harian */
        $harian = $harianQuery->get(['tanggal', 'status', 'catatan'])
            ->keyBy(fn (\App\Models\AbsensiHarian $item) => $item->tanggal->format('Y-m-d'));

        $rows = collect();
        foreach ($harian as $tanggal => $item) {
            $catatan = trim((string) ($item->catatan ?? ''));
            $rows->put((string) $tanggal, [
                'tanggal' => (string) $tanggal,
                'status' => (string) $item->status,
                'sumber' => 'Absensi Harian',
                'keterangan' => $catatan !== '' ? $catatan : '-',
            ]);
        }

        // ---- Absensi per jam: hanya melengkapi hari tanpa absensi harian; hanya kelas aktif ----
        $perJamQuery = \App\Models\AbsensiPerjam::query()
            ->join('jadwal_pertemuan as jp', 'jp.idjadwal_pertemuan', '=', 'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan')
            ->where('jp.semester_idsemester', $konteks->semester->idsemester)
            ->where('absensi_perjam.siswa_idsiswa', $siswaId)
            ->whereIn('jp.kelas_idkelas', $kelasIds);
        if ($relasi->tanggal_mulai) {
            $perJamQuery->whereDate('jp.tanggal', '>=', $relasi->tanggal_mulai);
        }
        if ($relasi->tanggal_berhenti) {
            $perJamQuery->whereDate('jp.tanggal', '<=', $relasi->tanggal_berhenti);
        }

        $perJam = $perJamQuery
            ->select('absensi_perjam.status', 'absensi_perjam.keterangan', 'jp.tanggal', 'jp.jam_ke')
            ->orderBy('jp.tanggal')
            ->orderBy('jp.jam_ke')
            ->get()
            ->groupBy(fn ($item) => substr((string) $item->tanggal, 0, 10));

        foreach ($perJam as $tanggal => $baris) {
            if ($rows->has($tanggal)) {
                continue;
            }

            $terpilih = null;
            foreach (\App\Services\RaporSiswaService::PRIORITAS_STATUS as $prioritas) {
                if ($baris->contains(fn ($item) => (string) $item->status === $prioritas)) {
                    $terpilih = $prioritas;
                    break;
                }
            }
            $terpilih = $terpilih ?? 'hadir';

            $kandidat = $baris->first(fn ($item) => (string) $item->status === $terpilih);
            $keterangan = trim((string) ($kandidat->keterangan ?? ''));

            $rows->put((string) $tanggal, [
                'tanggal' => (string) $tanggal,
                'status' => (string) $terpilih,
                'sumber' => 'Absensi Per Jam',
                'keterangan' => $keterangan !== '' ? $keterangan : '-',
            ]);
        }

        return $rows->sortKeys()->values();
    }

    /**
     * @return array{hadir:int, sakit:int, izin:int, alpha:int, total:int, per_tanggal:\Illuminate\Support\Collection, persen:string}
     */
    public static function rekapKehadiranWali(User $wali, int $siswaId): array
    {
        $relasi = self::pastikanAksesAkademikWali($wali, $siswaId);
        $konteks = KonteksAkademikService::ambil();

        $kosong = [
            'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'total' => 0,
            'per_tanggal' => collect(),
            'persen' => '0,0%',
        ];

        if (! $konteks->valid() || ! $konteks->tahunAjaran || ! $konteks->semester) {
            return $kosong;
        }

        // ---- Detail baris rekap (sumber tunggal dengan detailRekapKehadiranWali) ----
        $detail = self::detailRekapKehadiranWali($wali, $siswaId);

        /** @var \Illuminate\Support\Collection $perTanggal */
        $perTanggal = $detail->mapWithKeys(fn (array $row) => [$row['tanggal'] => $row['status']]);

        $rekap = [
            'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'total' => 0,
            'per_tanggal' => $perTanggal->sortKeys(),
        ];

        foreach ($perTanggal as $status) {
            if (array_key_exists($status, $rekap)) {
                $rekap[$status]++;
                $rekap['total']++;
            }
        }

        $persenValue = $rekap['total'] > 0 ? round($rekap['hadir'] / $rekap['total'] * 100, 1) : 0.0;
        $rekap['persen'] = number_format((float) $persenValue, 1, ',', '').'%';

        return $rekap;
    }
}
