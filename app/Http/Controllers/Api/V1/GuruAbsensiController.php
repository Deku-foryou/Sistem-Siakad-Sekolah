<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\AbsensiHarianStoreRequest;
use App\Http\Requests\Api\V1\AbsensiHarianUpdateRequest;
use App\Http\Requests\Api\V1\AbsensiPerjamStoreRequest;
use App\Http\Requests\Api\V1\AbsensiPerjamUpdateRequest;
use App\Http\Requests\Api\V1\GuruAbsensiRiwayatRequest;
use App\Http\Requests\Api\V1\PertemuanIndexRequest;
use App\Http\Resources\Api\V1\GuruAbsensiHarianResource;
use App\Http\Resources\Api\V1\GuruAbsensiPerjamResource;
use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\JadwalPertemuan;
use App\Models\Kelassiswa;
use App\Services\AbsensiHarianService;
use App\Services\AbsensiPerjamService;
use App\Services\GuruApiService;
use App\Services\JadwalPertemuanService;
use App\Services\KonteksAkademikService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API absensi guru (harian & per jam).
 *
 * Kebijakan yang dipertahankan dari aplikasi web:
 * - Guru adalah aktor pencatat; identitas selalu dari akun login.
 * - Guru hanya boleh mencatat pada kelas/jadwal yang menjadi kewenangannya.
 * - Harian: hanya hari ini & kemarin (H+1), konteks TA/semester aktif dari server.
 * - Per jam: snapshot jadwal_pertemuan; H+1 untuk edit; pertemuan masa depan ditolak.
 * - Seluruh proses bisnis lewat AbsensiHarianService / AbsensiPerjamService.
 */
class GuruAbsensiController extends \App\Http\Controllers\Controller
{
    public function __construct(
        private readonly AbsensiHarianService $harian,
        private readonly AbsensiPerjamService $perjam,
    ) {}

    private function pastikanGuru(Request $request)
    {
        try {
            return GuruApiService::pastikanGuru($request->user());
        } catch (DomainException) {
            return null;
        }
    }

    private function gagalGuru(): JsonResponse
    {
        return ApiResponse::fail('Akun guru ini belum terhubung ke data guru atau nonaktif. Hubungi administrator.', 403, [], 'GURU_NOT_LINKED');
    }

    // ====================================================== Absensi harian

    /**
     * GET /api/v1/guru/absensi/harian — riwayat absensi harian (paginated, ter-scope kelas yang boleh diakses).
     */
    public function harianIndex(GuruAbsensiRiwayatRequest $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();
        $kelasId = $request->integer('kelas_id') ?: null;

        if ($kelasId !== null && ! GuruApiService::bolehLihatKelas($guru, $kelasId, $konteks)) {
            return ApiResponse::fail('Kelas tidak ditemukan.', 404, [], 'KELAS_NOT_FOUND');
        }

        $base = GuruApiService::queryAbsensiHarian($guru, $konteks);

        if ($kelasId !== null) {
            $base->where('absensi_harian.kelas_idkelas', $kelasId);
        }

        if ($siswaId = $request->integer('siswa_id')) {
            $base->where('absensi_harian.siswa_idsiswa', $siswaId);
        }

        if ($dari = $request->input('dari')) {
            $base->whereDate('absensi_harian.tanggal', '>=', $dari);
        }

        if ($sampai = $request->input('sampai')) {
            $base->whereDate('absensi_harian.tanggal', '<=', $sampai);
        }

        if ($status = $request->input('status')) {
            $base->where('absensi_harian.status', $status);
        }

        $paginator = (clone $base)
            ->orderByDesc('absensi_harian.tanggal')
            ->orderByDesc('absensi_harian.idabsensi_harian')
            ->paginate((int) $request->input('per_page', 15), ['*'], 'page', (int) $request->input('page', 1));

        return ApiResponse::ok([
            'items' => GuruAbsensiHarianResource::collection($paginator->getCollection()),
        ], 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    /**
     * GET /api/v1/guru/absensi/harian/{id} — detail satu record (hanya kelas yang boleh diakses).
     */
    public function harianShow(Request $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();

        $absensi = AbsensiHarian::query()
            ->with(['kelas:idkelas,nama', 'siswa:idsiswa,nama'])
            ->where('idabsensi_harian', $id)
            ->first();

        if (! $absensi || ! GuruApiService::bolehLihatKelas($guru, (int) $absensi->kelas_idkelas, $konteks)) {
            return ApiResponse::fail('Absensi tidak ditemukan.', 404, [], 'ABSENSI_NOT_FOUND');
        }

        return ApiResponse::ok(new GuruAbsensiHarianResource($absensi), 'Berhasil');
    }

    /**
     * GET /api/v1/guru/kelas/{kelasId}/absensi?tanggal= — grid pengisian (siswa + absensi tercatat).
     * Konteks TA/semester aktif dari server; tanggal default hari ini.
     */
    public function harianGrid(Request $request, int $kelasId): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $validasi = $request->validate([
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $tanggal = $validasi['tanggal'] ?? now()->format('Y-m-d');
        $konteks = KonteksAkademikService::ambil();

        // Hanya kelas yang diajar (bukan wali-only) yang boleh mengisi absensi (kebijakan web).
        if (! $konteks->valid() || ! $konteks->tahunAjaran || ! $konteks->semester) {
            return ApiResponse::fail('Konteks akademik belum valid. Aktifkan tahun ajaran dan semester terlebih dahulu.', 422, [], 'KONTEKS_TIDAK_VALID');
        }

        if (! in_array($kelasId, GuruApiService::kelasIdsDiajar($guru, $konteks), true)) {
            return ApiResponse::fail('Kelas tidak ditemukan.', 404, [], 'KELAS_NOT_FOUND');
        }

        try {
            $this->harian->validateContext(
                (int) $konteks->tahunAjaran->idtahunajaran,
                (int) $konteks->semester->idsemester,
                $kelasId,
                $tanggal,
                $request->user()
            );
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'KONTEKS_TIDAK_VALID');
        }

        $siswaList = $this->harian->getSiswaAktif($kelasId, $request->user(), (int) $konteks->tahunAjaran->idtahunajaran, (int) $konteks->semester->idsemester);
        $records = $this->harian->getAbsensiKelasHari(
            (int) $konteks->tahunAjaran->idtahunajaran,
            (int) $konteks->semester->idsemester,
            $kelasId,
            $tanggal,
            $request->user()
        )->keyBy('siswa_idsiswa');

        $siswa = $siswaList->map(fn ($ks) => [
            'id' => $ks->siswa?->idsiswa,
            'nisn' => $ks->siswa?->nisn,
            'nama' => $ks->siswa?->nama,
            'absensi' => ($record = $records->get($ks->siswa_idsiswa)) ? [
                'id' => $record->idabsensi_harian,
                'status' => $record->status,
                'catatan' => $record->catatan,
            ] : null,
        ])->values();

        return ApiResponse::ok([
            'tanggal' => $tanggal,
            'kelas_id' => $kelasId,
            'konteks' => GuruApiService::konteksPayload($konteks),
            'siswa' => $siswa,
        ], 'Berhasil');
    }

    /**
     * POST /api/v1/guru/absensi/harian — simpan massal (upsert per siswa).
     * Upsert: catatan yang sudah ada diperbarui; yang belum dibuat.
     */
    public function harianStore(AbsensiHarianStoreRequest $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $tanggal = (string) $request->input('tanggal');
        $kelasId = (int) $request->input('kelas_id');
        $records = $request->input('records');

        $siswaIds = array_map(fn ($row) => (int) $row['siswa_id'], $records);
        if (count($siswaIds) !== count(array_unique($siswaIds))) {
            return ApiResponse::fail('Terdapat siswa yang sama lebih dari satu kali pada records.', 422, [], 'DUPLIKAT_SISWA');
        }

        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->tahunAjaran || ! $konteks->semester) {
            return ApiResponse::fail('Konteks akademik belum valid. Aktifkan tahun ajaran dan semester terlebih dahulu.', 422, [], 'KONTEKS_TIDAK_VALID');
        }

        $tahunAjaranId = (int) $konteks->tahunAjaran->idtahunajaran;
        $semesterId = (int) $konteks->semester->idsemester;

        try {
            $this->harian->validateContext($tahunAjaranId, $semesterId, $kelasId, $tanggal, $request->user());
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'KONTEKS_TIDAK_VALID');
        }

        $user = $request->user();
        $userId = (int) $user->getKey();

        $existingMap = AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $tahunAjaranId)
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->whereDate('tanggal', $tanggal)
            ->whereIn('siswa_idsiswa', $siswaIds)
            ->pluck('idabsensi_harian', 'siswa_idsiswa');

        $untukDibuat = [];
        $untukDiubah = [];

        foreach ($records as $row) {
            $siswaId = (int) $row['siswa_id'];
            $data = ['status' => $row['status'], 'catatan' => $row['catatan'] ?? null];

            if ($existingMap->has($siswaId)) {
                $untukDiubah[$existingMap[$siswaId]] = $data;
            } else {
                $untukDibuat[$siswaId] = $data;
            }
        }

        try {
            DB::transaction(function () use ($tahunAjaranId, $semesterId, $kelasId, $tanggal, $untukDibuat, $untukDiubah, $userId, $user) {
                if ($untukDibuat !== []) {
                    $this->harian->saveAbsensiMassal(
                        $tahunAjaranId,
                        $semesterId,
                        $kelasId,
                        $tanggal,
                        $untukDibuat,
                        $userId,
                        $user
                    );
                }

                foreach ($untukDiubah as $absensiId => $data) {
                    $this->harian->authorizeRecordForEdit($user, (int) $absensiId, $tahunAjaranId, $semesterId, $kelasId, $tanggal);
                    $this->harian->editAbsensi(
                        (int) $absensiId,
                        $data['status'],
                        $data['catatan'],
                        $userId,
                        $user,
                        $tahunAjaranId,
                        $semesterId,
                        $kelasId,
                        $tanggal
                    );
                }
            });
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'GAGAL_SIMPAN');
        }

        return ApiResponse::ok([
            'tanggal' => $tanggal,
            'kelas_id' => $kelasId,
            'dibuat' => count($untukDibuat),
            'diubah' => count($untukDiubah),
        ], 'Absensi berhasil disimpan.');
    }

    /**
     * PATCH /api/v1/guru/absensi/harian/{id} — ubah status/catatan satu record.
     */
    public function harianUpdate(AbsensiHarianUpdateRequest $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $absensi = AbsensiHarian::query()->where('idabsensi_harian', $id)->first();

        // Anti-IDOR: guru hanya boleh mengubah absensi pada kelas yang ia ajar.
        $konteksAjar = KonteksAkademikService::ambil();
        if (! $absensi || ! in_array((int) $absensi->kelas_idkelas, GuruApiService::kelasIdsDiajar($guru, $konteksAjar), true)) {
            return ApiResponse::fail('Absensi tidak ditemukan.', 404, [], 'ABSENSI_NOT_FOUND');
        }

        $user = $request->user();

        try {
            $this->harian->authorizeRecordForEdit(
                $user,
                $id,
                (int) $absensi->tahunajaran_idtahunajaran,
                (int) $absensi->semester_idsemester,
                (int) $absensi->kelas_idkelas,
                $absensi->tanggal->format('Y-m-d')
            );

            $diubah = $this->harian->editAbsensi(
                $id,
                (string) $request->input('status'),
                $request->input('catatan'),
                (int) $user->getKey(),
                $user,
                (int) $absensi->tahunajaran_idtahunajaran,
                (int) $absensi->semester_idsemester,
                (int) $absensi->kelas_idkelas,
                $absensi->tanggal->format('Y-m-d')
            );
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'GAGAL_UBAH');
        }

        return ApiResponse::ok(new GuruAbsensiHarianResource($diubah->load(['kelas:idkelas,nama', 'siswa:idsiswa,nama'])), 'Absensi berhasil diperbarui.');
    }

    // ====================================================== Absensi per jam

    /**
     * GET /api/v1/guru/absensi/perjam — riwayat absensi per jam (paginated, ter-scope guru).
     */
    public function perjamIndex(GuruAbsensiRiwayatRequest $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();
        $kelasId = $request->integer('kelas_id') ?: null;

        if ($kelasId !== null && ! GuruApiService::bolehLihatKelas($guru, $kelasId, $konteks)) {
            return ApiResponse::fail('Kelas tidak ditemukan.', 404, [], 'KELAS_NOT_FOUND');
        }

        $base = AbsensiPerjam::query()
            ->join('jadwal_pertemuan as jp', 'jp.idjadwal_pertemuan', '=', 'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan')
            ->join('kelas as k', 'k.idkelas', '=', 'jp.kelas_idkelas')
            ->join('mapel as m', 'm.idmapel', '=', 'jp.mapel_idmapel')
            ->join('siswa as s', 's.idsiswa', '=', 'absensi_perjam.siswa_idsiswa')
            ->where('jp.guru_idguru', $guru->idguru);

        if ($kelasId !== null) {
            $base->where('jp.kelas_idkelas', $kelasId);
        }

        if ($siswaId = $request->integer('siswa_id')) {
            $base->where('absensi_perjam.siswa_idsiswa', $siswaId);
        }

        if ($dari = $request->input('dari')) {
            $base->where('jp.tanggal', '>=', $dari);
        }

        if ($sampai = $request->input('sampai')) {
            $base->where('jp.tanggal', '<=', $sampai);
        }

        if ($status = $request->input('status')) {
            $base->where('absensi_perjam.status', $status);
        }

        $paginator = (clone $base)
            ->select(
                'absensi_perjam.idabsensi_perjam',
                'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan',
                'absensi_perjam.siswa_idsiswa',
                'absensi_perjam.status',
                'absensi_perjam.keterangan',
                'jp.tanggal',
                'jp.hari',
                'jp.jam_ke',
                'jp.waktu_mulai',
                'jp.waktu_selesai',
                'jp.status as pertemuan_status',
                'jp.kelas_idkelas',
                'k.nama as kelas_nama',
                'm.nama as mapel_nama',
                's.nama as siswa_nama',
                's.idsiswa as siswa_id'
            )
            ->orderByDesc('jp.tanggal')
            ->orderByDesc('jp.jam_ke')
            ->orderByDesc('absensi_perjam.idabsensi_perjam')
            ->paginate((int) $request->input('per_page', 15), ['*'], 'page', (int) $request->input('page', 1));

        return ApiResponse::ok([
            'items' => GuruAbsensiPerjamResource::collection($paginator->getCollection()),
        ], 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    /**
     * GET /api/v1/guru/absensi/perjam/pertemuan?tanggal= — pertemuan milik guru pada tanggal (default hari ini).
     * Membuat snapshot pertemuan yang belum ada (idempoten; konsisten dengan halaman /absensi-perjam).
     */
    public function perjamPertemuanIndex(PertemuanIndexRequest $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $tanggal = $request->input('tanggal') ?? now()->format('Y-m-d');

        try {
            app(JadwalPertemuanService::class)->generateUntukTanggal($tanggal);
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'KONTEKS_TIDAK_VALID');
        }

        $pertemuan = JadwalPertemuan::query()
            ->with(['kelas:idkelas,nama', 'mapel:idmapel,kode,nama'])
            ->where('guru_idguru', $guru->idguru)
            ->where('tanggal', $tanggal)
            ->get()
            ->sortBy(fn ($p) => [(int) $p->hari, (int) ($p->jam_ke ?? 999), (string) $p->waktu_mulai])
            ->values();

        $kelasIds = $pertemuan->pluck('kelas_idkelas')->unique()->values();
        $jumlahSiswa = Kelassiswa::query()
            ->whereIn('kelas_idkelas', $kelasIds === [] ? [0] : $kelasIds)
            ->where('status', 'aktif')
            ->select('kelas_idkelas', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('kelas_idkelas')
            ->pluck('jumlah', 'kelas_idkelas');

        $pertemuanIds = $pertemuan->pluck('idjadwal_pertemuan');
        $jumlahAbsensi = AbsensiPerjam::query()
            ->whereIn('jadwal_pertemuan_idjadwal_pertemuan', $pertemuanIds === [] ? [0] : $pertemuanIds)
            ->select('jadwal_pertemuan_idjadwal_pertemuan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('jadwal_pertemuan_idjadwal_pertemuan')
            ->pluck('jumlah', 'jadwal_pertemuan_idjadwal_pertemuan');

        $payload = $pertemuan->map(fn ($p) => [
            'id' => $p->idjadwal_pertemuan,
            'jadwal_kelas_id' => $p->jadwalkelas_idjadwalkelas,
            'tanggal' => $tanggal,
            'hari' => (int) $p->hari,
            'jam_ke' => $p->jam_ke,
            'waktu_mulai' => $this->jam($p->waktu_mulai),
            'waktu_selesai' => $this->jam($p->waktu_selesai),
            'status' => (string) $p->status,
            'kelas' => ['id' => $p->kelas?->idkelas, 'nama' => $p->kelas?->nama],
            'mapel' => ['id' => $p->mapel?->idmapel, 'kode' => $p->mapel?->kode, 'nama' => $p->mapel?->nama],
            'jumlah_siswa' => (int) ($jumlahSiswa[$p->kelas_idkelas] ?? 0),
            'jumlah_absensi' => (int) ($jumlahAbsensi[$p->idjadwal_pertemuan] ?? 0),
            'bisa_diabsen' => $p->status === 'terjadwal' && ! \Carbon\Carbon::parse($p->tanggal)->isAfter(now()->startOfDay()),
        ]);

        return ApiResponse::ok([
            'tanggal' => $tanggal,
            'pertemuan' => $payload,
        ], 'Berhasil');
    }

    /**
     * GET /api/v1/guru/absensi/perjam/pertemuan/{id} — detail pertemuan + siswa + absensi (grid pengisian).
     */
    public function perjamPertemuanShow(Request $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $pertemuan = JadwalPertemuan::query()
            ->with(['kelas:idkelas,nama', 'mapel:idmapel,kode,nama'])
            ->where('idjadwal_pertemuan', $id)
            ->first();

        if (! $pertemuan || (int) $pertemuan->guru_idguru !== (int) $guru->idguru) {
            return ApiResponse::fail('Pertemuan tidak ditemukan.', 404, [], 'PERTEMUAN_NOT_FOUND');
        }

        $siswaList = Kelassiswa::query()
            ->where('kelas_idkelas', $pertemuan->kelas_idkelas)
            ->where('status', 'aktif')
            ->whereHas('siswa', fn ($query) => $query->where('status', 'aktif'))
            ->with('siswa:idsiswa,nisn,nis,nama')
            ->orderBy('siswa_idsiswa')
            ->get();

        $absensi = AbsensiPerjam::query()
            ->where('jadwal_pertemuan_idjadwal_pertemuan', $pertemuan->idjadwal_pertemuan)
            ->get()
            ->keyBy('siswa_idsiswa');

        $siswa = $siswaList->map(fn ($ks) => [
            'id' => $ks->siswa?->idsiswa,
            'nisn' => $ks->siswa?->nisn,
            'nama' => $ks->siswa?->nama,
            'absensi' => ($record = $absensi->get($ks->siswa_idsiswa)) ? [
                'id' => $record->idabsensi_perjam,
                'status' => $record->status,
                'keterangan' => $record->keterangan,
            ] : null,
        ])->values();

        return ApiResponse::ok([
            'id' => $pertemuan->idjadwal_pertemuan,
            'tanggal' => substr((string) $pertemuan->tanggal?->format('Y-m-d'), 0, 10),
            'hari' => (int) $pertemuan->hari,
            'jam_ke' => $pertemuan->jam_ke,
            'waktu_mulai' => $this->jam($pertemuan->waktu_mulai),
            'waktu_selesai' => $this->jam($pertemuan->waktu_selesai),
            'status' => (string) $pertemuan->status,
            'bisa_diabsen' => $pertemuan->status === 'terjadwal' && ! $pertemuan->tanggal->isAfter(now()->startOfDay()),
            'kelas' => ['id' => $pertemuan->kelas?->idkelas, 'nama' => $pertemuan->kelas?->nama],
            'mapel' => ['id' => $pertemuan->mapel?->idmapel, 'kode' => $pertemuan->mapel?->kode, 'nama' => $pertemuan->mapel?->nama],
            'siswa' => $siswa,
        ], 'Berhasil');
    }

    /**
     * GET /api/v1/guru/absensi/perjam/{id} — detail satu record absensi (hanya pertemuan milik guru).
     */
    public function perjamShow(Request $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $absensi = AbsensiPerjam::query()
            ->with(['jadwalPertemuan.kelas:idkelas,nama', 'jadwalPertemuan.mapel:idmapel,kode,nama', 'siswa:idsiswa,nama,nisn'])
            ->where('idabsensi_perjam', $id)
            ->first();

        if (! $absensi || (int) ($absensi->jadwalPertemuan?->guru_idguru ?? 0) !== (int) $guru->idguru) {
            return ApiResponse::fail('Absensi tidak ditemukan.', 404, [], 'ABSENSI_NOT_FOUND');
        }

        $p = $absensi->jadwalPertemuan;

        return ApiResponse::ok([
            'id' => $absensi->idabsensi_perjam,
            'status' => $absensi->status,
            'keterangan' => $absensi->keterangan,
            'jadwal_pertemuan_id' => $p?->idjadwal_pertemuan,
            'tanggal' => $p?->tanggal?->format('Y-m-d'),
            'hari' => (int) ($p?->hari ?? 0),
            'jam_ke' => $p?->jam_ke,
            'waktu_mulai' => $this->jam($p?->waktu_mulai),
            'waktu_selesai' => $this->jam($p?->waktu_selesai),
            'pertemuan_status' => (string) ($p?->status ?? ''),
            'kelas' => ['id' => $p?->kelas?->idkelas, 'nama' => $p?->kelas?->nama],
            'mapel' => ['id' => $p?->mapel?->idmapel, 'kode' => $p?->mapel?->kode, 'nama' => $p?->mapel?->nama],
            'siswa' => ['id' => $absensi->siswa?->idsiswa, 'nisn' => $absensi->siswa?->nisn, 'nama' => $absensi->siswa?->nama],
        ], 'Berhasil');
    }

    /**
     * POST /api/v1/guru/absensi/perjam — catat massal pada satu pertemuan (upsert per siswa).
     */
    public function perjamStore(AbsensiPerjamStoreRequest $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $pertemuanId = (int) $request->input('jadwal_pertemuan_id');
        $records = $request->input('records');

        $siswaIds = array_map(fn ($row) => (int) $row['siswa_id'], $records);
        if (count($siswaIds) !== count(array_unique($siswaIds))) {
            return ApiResponse::fail('Terdapat siswa yang sama lebih dari satu kali pada records.', 422, [], 'DUPLIKAT_SISWA');
        }

        $pertemuan = JadwalPertemuan::query()->where('idjadwal_pertemuan', $pertemuanId)->first();

        if (! $pertemuan || (int) $pertemuan->guru_idguru !== (int) $guru->idguru) {
            return ApiResponse::fail('Pertemuan tidak ditemukan.', 404, [], 'PERTEMUAN_NOT_FOUND');
        }

        $user = $request->user();

        $existingMap = AbsensiPerjam::query()
            ->where('jadwal_pertemuan_idjadwal_pertemuan', $pertemuanId)
            ->whereIn('siswa_idsiswa', $siswaIds)
            ->pluck('idabsensi_perjam', 'siswa_idsiswa');

        $dibuat = 0;
        $diubah = 0;

        try {
            DB::transaction(function () use ($pertemuanId, $records, $existingMap, $user, &$dibuat, &$diubah) {
                foreach ($records as $row) {
                    $siswaId = (int) $row['siswa_id'];
                    $status = (string) $row['status'];
                    $keterangan = $row['keterangan'] ?? null;

                    if ($existingMap->has($siswaId)) {
                        $this->perjam->edit((int) $existingMap[$siswaId], $status, $keterangan, $user);
                        $diubah++;
                    } else {
                        $this->perjam->simpan($pertemuanId, $siswaId, $status, $keterangan, $user);
                        $dibuat++;
                    }
                }
            });
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'GAGAL_SIMPAN');
        }

        return ApiResponse::ok([
            'jadwal_pertemuan_id' => $pertemuanId,
            'dibuat' => $dibuat,
            'diubah' => $diubah,
        ], 'Absensi per jam berhasil disimpan.');
    }

    /**
     * PATCH /api/v1/guru/absensi/perjam/{id} — ubah status/keterangan satu record (H+1).
     */
    public function perjamUpdate(AbsensiPerjamUpdateRequest $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $absensi = AbsensiPerjam::query()
            ->with('jadwalPertemuan:idjadwal_pertemuan,guru_idguru')
            ->where('idabsensi_perjam', $id)
            ->first();

        if (! $absensi || (int) ($absensi->jadwalPertemuan?->guru_idguru ?? 0) !== (int) $guru->idguru) {
            return ApiResponse::fail('Absensi tidak ditemukan.', 404, [], 'ABSENSI_NOT_FOUND');
        }

        try {
            $diubah = $this->perjam->edit(
                $id,
                (string) $request->input('status'),
                $request->input('keterangan'),
                $request->user()
            );
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'GAGAL_UBAH');
        }

        return ApiResponse::ok([
            'id' => $diubah->idabsensi_perjam,
            'status' => $diubah->status,
            'keterangan' => $diubah->keterangan,
        ], 'Absensi per jam berhasil diperbarui.');
    }

    // ====================================================== Helpers

    private function jam(mixed $waktu): ?string
    {
        $s = substr((string) $waktu, 0, 5);

        return $s === '' ? null : $s;
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
