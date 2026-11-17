<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\AbsensiFilterRequest;
use App\Http\Requests\Api\V1\AbsensiPeriodeRequest;
use App\Http\Requests\Api\V1\NilaiIndexRequest;
use App\Http\Requests\Api\V1\PermintaanPutusRequest;
use App\Http\Resources\Api\V1\AbsensiHarianResource;
use App\Http\Resources\Api\V1\AbsensiPerjamResource;
use App\Http\Resources\Api\V1\BerandaResource;
use App\Http\Resources\Api\V1\JadwalResource;
use App\Http\Resources\Api\V1\NilaiKelompokResource;
use App\Http\Resources\Api\V1\RaporDetailResource;
use App\Http\Resources\Api\V1\RaporResource;
use App\Models\Siswa;
use App\Models\Walimurid;
use App\Services\JadwalSiswaService;
use App\Services\KonteksAkademikService;
use App\Services\RaporSiswaService;
use App\Services\RekapAbsensiService;
use App\Services\WalimuridService;
use App\Support\ApiResponse;
use App\Support\Hari;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API anak/relasi wali murid.
 *
 * - Daftar relasi: semua status milik wali (pending/verified/rejected/terminated) — lihat sendiri.
 * - Akses akademik: hanya relasi `verified` tanpa tanggal_berhenti; dijaga
 *   WalimuridService::pastikanAksesAkademikWali() (403 bila tidak memenuhi).
 * - Identitas anak tidak pernah dari payload; selalu dari path parameter
 *   {siswaId} yang divalidasi terhadap relasi milik wali login.
 * - Tidak ada pengajuan relasi baru (self-registration ditolak kebijakan).
 */
class WaliAnakController extends \App\Http\Controllers\Controller
{
    /**
     * Pastikan wali login; return User. 403 bila bukan wali/nonaktif.
     */
    private function pastikanWali(Request $request): ?\App\Models\User
    {
        $user = $request->user();

        if (! $user || $user->level !== 'wali' || $user->status_akun !== 'aktif') {
            return null;
        }

        return $user;
    }

    private function gagalWali(): JsonResponse
    {
        return ApiResponse::fail('Akun wali tidak aktif. Hubungi admin sekolah.', 403, [], 'WALI_NOT_ACTIVE');
    }

    /**
     * Gerbang akademik: relasi milik wali + verified + belum berakhir + konteks valid.
     * abort(403) dari service dirender JSON (FORBIDDEN) oleh Handler.
     */
    private function pastikanAksesAkademik(\App\Models\User $wali, int $siswaId): Walimurid
    {
        return WalimuridService::pastikanAksesAkademikWali($wali, $siswaId);
    }

    /** User siswa untuk reuse service siswa (Beranda/Rapor/Nilai). */
    private function userSiswa(Siswa $siswa): ?\App\Models\User
    {
        return $siswa->user;
    }

    // ------------------------------------------------------------------ A. Relasi

    /**
     * GET /api/v1/wali/anak — daftar semua relasi milik wali (semua status).
     */
    public function index(Request $request): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $relasiList = Walimurid::query()
            ->with('siswa')
            ->where('user_iduser', $wali->id)
            ->orderBy('idwalimurid')
            ->get();

        $konteks = KonteksAkademikService::ambil();
        $anakIds = $relasiList->where('status_hubungan', 'verified')->pluck('siswa_idsiswa')->all();
        $kelasLabel = WalimuridService::labelKelasSiswa($anakIds);

        $items = $relasiList->map(fn (Walimurid $relasi) => $this->payloadRelasi($relasi, $kelasLabel))->values();

        return ApiResponse::ok([
            'konteks' => $this->konteksPayload($konteks),
            'anak' => $items,
        ], 'Berhasil');
    }

    /**
     * GET /api/v1/wali/anak/{siswaId} — detail relasi (+ histori append-only).
     */
    public function show(Request $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $relasi = Walimurid::query()
            ->with(['siswa', 'histories' => fn ($query) => $query->orderByRaw('created_at DESC')->orderByRaw('id DESC')])
            ->where('user_iduser', $wali->id)
            ->where('siswa_idsiswa', $siswaId)
            ->first();

        if (! $relasi) {
            return ApiResponse::fail('Data anak tidak ditemukan.', 404, [], 'ANAK_NOT_FOUND');
        }

        $konteks = KonteksAkademikService::ambil();
        $kelasLabel = WalimuridService::labelKelasSiswa([$siswaId]);
        $payload = $this->payloadRelasi($relasi, $kelasLabel);
        $payload['histori'] = $relasi->histories->map(fn ($h) => [
            'event' => $h->event,
            'status_sebelum' => $h->status_sebelum,
            'status_sesudah' => $h->status_sesudah,
            'aktor_peran' => $h->aktor_peran,
            'alasan' => $h->alasan,
            'tanggal_mulai_sesudah' => $h->tanggal_mulai_sesudah?->toDateString(),
            'tanggal_berhenti_sesudah' => $h->tanggal_berhenti_sesudah?->toDateString(),
            'created_at' => $h->created_at ? (string) $h->created_at : null,
        ])->values();

        return ApiResponse::ok($payload, 'Berhasil');
    }

    /**
     * POST /api/v1/wali/anak/{siswaId}/permintaan-putus — pengajuan penghentian hubungan.
     * Keputusan tetap oleh admin/superadmin (W4); wali tidak bisa menyetujui sendiri.
     */
    public function permintaanPutus(PermintaanPutusRequest $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $relasi = Walimurid::query()
            ->where('user_iduser', $wali->id)
            ->where('siswa_idsiswa', $siswaId)
            ->first();

        if (! $relasi) {
            return ApiResponse::fail('Data anak tidak ditemukan.', 404, [], 'ANAK_NOT_FOUND');
        }

        try {
            $permintaan = WalimuridService::buatPermintaanPutus($wali, (int) $relasi->idwalimurid, (string) $request->input('alasan'));
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'GAGAL_AJUKAN');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'GAGAL_AJUKAN');
        }

        return ApiResponse::ok([
            'id' => $permintaan->idpermintaan_putus,
            'status_permintaan' => $permintaan->status_permintaan,
            'alasan' => $permintaan->alasan,
        ], 'Permintaan penghentian hubungan berhasil diajukan dan menunggu keputusan admin.', 201);
    }

    // ------------------------------------------------------- B. Akademik anak

    /**
     * GET /api/v1/wali/anak/{siswaId}/beranda — beranda anak (identik portal siswa).
     */
    public function beranda(Request $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $siswaUser = $this->userSiswa($siswa);
        if (! $siswaUser) {
            return ApiResponse::fail('Akun anak belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        return ApiResponse::ok(new BerandaResource(\App\Services\BerandaSiswaService::beranda($siswaUser)), 'Berhasil');
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/jadwal?hari=&tanggal=
     */
    public function jadwal(Request $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $konteks = KonteksAkademikService::ambil();
        $jadwalSiswa = app(JadwalSiswaService::class);

        $tanggal = $request->input('tanggal');
        $hari = $request->input('hari');

        if ($tanggal) {
            $hari = (int) \Carbon\Carbon::parse($tanggal)->dayOfWeekIso;
        } elseif ($hari !== null) {
            $hari = (int) $hari;
        }

        $kelasList = $jadwalSiswa->kelasAktif($siswa->idsiswa, $konteks);
        $kelasIds = $kelasList->pluck('idkelas')->all();

        $items = $jadwalSiswa->urutkan($jadwalSiswa->jadwal($konteks, $kelasIds, $hari));

        return ApiResponse::ok([
            'tanggal' => $tanggal ?: null,
            'hari' => $hari,
            'nama_hari' => $hari !== null ? Hari::nama($hari) : null,
            'konteks' => $this->konteksPayload($konteks),
            'jadwal' => JadwalResource::collection($items),
        ], 'Berhasil');
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/jadwal/hari-ini
     */
    public function jadwalHariIni(Request $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $konteks = KonteksAkademikService::ambil();
        $jadwalSiswa = app(JadwalSiswaService::class);
        $kelasList = $jadwalSiswa->kelasAktif($siswa->idsiswa, $konteks);
        $kelasIds = $kelasList->pluck('idkelas')->all();
        $hari = now()->dayOfWeekIso;

        $items = $jadwalSiswa->urutkan($jadwalSiswa->jadwal($konteks, $kelasIds, $hari));

        return ApiResponse::ok([
            'tanggal' => now()->format('Y-m-d'),
            'hari' => $hari,
            'nama_hari' => Hari::nama($hari),
            'konteks' => $this->konteksPayload($konteks),
            'jadwal' => JadwalResource::collection($items),
        ], 'Berhasil');
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/absensi/harian
     */
    public function absensiHarian(AbsensiFilterRequest $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $konteks = KonteksAkademikService::ambil();
        $rekap = app(RekapAbsensiService::class);

        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $status = $request->input('status');

        $rekapSemester = $rekap->ringkasan($rekap->queryHarian($siswa->idsiswa, $konteks, true), 'absensi_harian.status');

        $base = $rekap->queryHarian($siswa->idsiswa, $konteks, false, $dari, $sampai, $status);
        $ringkasan = $rekap->ringkasan($base, 'absensi_harian.status');

        $paginator = (clone $base)
            ->with(['kelas:idkelas,nama'])
            ->orderByDesc('absensi_harian.tanggal')
            ->orderByDesc('absensi_harian.idabsensi_harian')
            ->paginate((int) $request->input('per_page', 15), ['*'], 'page', (int) $request->input('page', 1));

        return ApiResponse::ok([
            'konteks' => $this->konteksPayload($konteks),
            'rekap_semester' => $this->ringkasanPayload($rekapSemester, $rekap),
            'ringkasan' => $this->ringkasanPayload($ringkasan, $rekap),
            'filter' => ['dari' => $dari, 'sampai' => $sampai, 'status' => $status],
            'items' => AbsensiHarianResource::collection($paginator->getCollection()),
        ], 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/absensi/periode?dari=&sampai=
     */
    public function absensiPeriode(AbsensiPeriodeRequest $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $konteks = KonteksAkademikService::ambil();
        $rekap = app(RekapAbsensiService::class);

        $dari = (string) $request->input('dari');
        $sampai = (string) $request->input('sampai');
        $status = $request->input('status');

        $base = $rekap->queryHarian($siswa->idsiswa, $konteks, false, $dari, $sampai, $status);
        $ringkasan = $rekap->ringkasan($base, 'absensi_harian.status');

        $paginator = (clone $base)
            ->with(['kelas:idkelas,nama'])
            ->orderByDesc('absensi_harian.tanggal')
            ->orderByDesc('absensi_harian.idabsensi_harian')
            ->paginate((int) $request->input('per_page', 15), ['*'], 'page', (int) $request->input('page', 1));

        return ApiResponse::ok([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'ringkasan' => $this->ringkasanPayload($ringkasan, $rekap),
            'filter' => ['dari' => $dari, 'sampai' => $sampai, 'status' => $status],
            'items' => AbsensiHarianResource::collection($paginator->getCollection()),
        ], 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/absensi/perjam
     */
    public function absensiPerjam(AbsensiFilterRequest $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $rekap = app(RekapAbsensiService::class);

        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $status = $request->input('status');

        $rekapTotal = $rekap->ringkasan($rekap->queryPerJam($siswa->idsiswa), 'absensi_perjam.status');

        $base = $rekap->queryPerJam($siswa->idsiswa, $dari, $sampai, $status);
        $ringkasan = $rekap->ringkasan($base, 'absensi_perjam.status');

        $paginator = (clone $base)
            ->select(
                'absensi_perjam.*',
                'jp.tanggal',
                'jp.hari',
                'jp.jam_ke',
                'jp.waktu_mulai',
                'jp.waktu_selesai',
                'jp.status as pertemuan_status',
                'k.nama as kelas_nama',
                'm.nama as mapel_nama',
                'g.nama as guru_nama'
            )
            ->orderByDesc('jp.tanggal')
            ->orderByDesc('jp.jam_ke')
            ->paginate((int) $request->input('per_page', 15), ['*'], 'page', (int) $request->input('page', 1));

        return ApiResponse::ok([
            'rekap' => $this->ringkasanPayload($ringkasan, $rekap),
            'rekap_total' => $this->ringkasanPayload($rekapTotal, $rekap),
            'filter' => ['dari' => $dari, 'sampai' => $sampai, 'status' => $status],
            'items' => AbsensiPerjamResource::collection($paginator->getCollection()),
        ], 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/nilai?semester_id=&kelas_id=
     */
    public function nilai(NilaiIndexRequest $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $siswaUser = $this->userSiswa($siswa);
        if (! $siswaUser) {
            return ApiResponse::fail('Akun anak belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        $semesterId = (int) $request->input('semester_id') ?: null;
        $kelasId = (int) $request->input('kelas_id') ?: null;

        $kelompok = RaporSiswaService::nilaiFinal($siswaUser, $semesterId, $kelasId);

        if (($semesterId !== null || $kelasId !== null) && $kelompok->isEmpty()) {
            return ApiResponse::fail('Nilai untuk filter tersebut tidak tersedia.', 404, [], 'NILAI_NOT_FOUND');
        }

        return ApiResponse::ok([
            'rapor' => NilaiKelompokResource::collection($kelompok),
        ], 'Berhasil');
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/rapor — daftar rapor final anak.
     */
    public function rapor(Request $request, int $siswaId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $siswaUser = $this->userSiswa($siswa);
        if (! $siswaUser) {
            return ApiResponse::fail('Akun anak belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        try {
            $daftar = RaporSiswaService::daftarRapor($siswaUser);
        } catch (DomainException $e) {
            return ApiResponse::fail('Akun anak belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        return ApiResponse::ok(['rapor' => RaporResource::collection($daftar)], 'Berhasil');
    }

    /**
     * GET /api/v1/wali/anak/{siswaId}/rapor/{semesterId}/{kelasId} — detail rapor final anak.
     */
    public function raporDetail(Request $request, int $siswaId, int $semesterId, int $kelasId): JsonResponse
    {
        $wali = $this->pastikanWali($request);
        if (! $wali) {
            return $this->gagalWali();
        }

        $siswa = $this->anakUntukAkademik($wali, $siswaId);
        if ($siswa instanceof JsonResponse) {
            return $siswa;
        }

        $siswaUser = $this->userSiswa($siswa);
        if (! $siswaUser) {
            return ApiResponse::fail('Akun anak belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        try {
            $detail = RaporSiswaService::detail($siswaUser, $semesterId, $kelasId);
        } catch (DomainException $e) {
            return ApiResponse::fail('Rapor ini tidak tersedia.', 404, [], 'RAPOR_NOT_FOUND');
        }

        return ApiResponse::ok(new RaporDetailResource($detail), 'Berhasil');
    }

    // ------------------------------------------------------------------ Helpers

    /**
     * Gate akademik: relasi milik wali + verified + belum berakhir + siswa aktif.
     * Return Siswa atau JsonResponse error (403/404).
     */
    private function anakUntukAkademik(\App\Models\User $wali, int $siswaId): Siswa|JsonResponse
    {
        try {
            $this->pastikanAksesAkademik($wali, $siswaId);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Gerbang ditolak (403) — relasi pending/rejected/terminated/berakhir.
            return ApiResponse::fail(trim((string) $e->getMessage()) ?: 'Akses akademik tidak tersedia untuk siswa ini.', 403, [], 'AKSES_DITOLAK');
        }

        $siswa = Siswa::query()->where('idsiswa', $siswaId)->first();

        if (! $siswa || $siswa->status !== 'aktif') {
            return ApiResponse::fail('Siswa tidak ditemukan.', 404, [], 'SISWA_NOT_FOUND');
        }

        return $siswa;
    }

    private function payloadRelasi(Walimurid $relasi, array $kelasLabel): array
    {
        $siswa = $relasi->siswa;
        $aksesAkademik = $relasi->status_hubungan === 'verified'
            && ! $relasi->tanggal_berhenti
            && $relasi->tanggal_mulai !== null
            && $relasi->tanggal_mulai->lessThanOrEqualTo(now()->startOfDay());

        return [
            'relasi_id' => $relasi->idwalimurid,
            'hubungan' => $relasi->hubungan,
            'status_hubungan' => $relasi->status_hubungan,
            'tanggal_mulai' => $relasi->tanggal_mulai?->toDateString(),
            'tanggal_berhenti' => $relasi->tanggal_berhenti?->toDateString(),
            'tanggal_verifikasi' => $relasi->tanggal_verifikasi?->toIso8601String(),
            'bisa_akses_akademik' => $aksesAkademik,
            'kelas' => $kelasLabel[(int) $relasi->siswa_idsiswa] ?? null,
            'siswa' => $siswa ? [
                'id' => $siswa->idsiswa,
                'nisn' => $siswa->nisn,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'tahun_masuk' => $siswa->tahun_masuk,
                'status' => $siswa->status,
            ] : null,
        ];
    }

    private function konteksPayload($konteks): array
    {
        return [
            'valid' => $konteks->valid(),
            'peringatan' => $konteks->pesanPeringatan(),
            'tahun_ajaran' => $konteks->tahunAjaran ? ['id' => $konteks->tahunAjaran->idtahunajaran, 'nama' => $konteks->tahunAjaran->nama] : null,
            'semester' => $konteks->semester ? ['id' => $konteks->semester->idsemester, 'nama' => $konteks->semester->nama] : null,
        ];
    }

    private function ringkasanPayload(array $ringkasan, RekapAbsensiService $rekap): array
    {
        return [
            'total' => (int) $ringkasan['total'],
            'hadir' => (int) $ringkasan['hadir'],
            'sakit' => (int) $ringkasan['sakit'],
            'izin' => (int) $ringkasan['izin'],
            'alpha' => (int) $ringkasan['alpha'],
            'persen_hadir' => $rekap->persen((int) $ringkasan['hadir'], (int) $ringkasan['total']),
        ];
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
