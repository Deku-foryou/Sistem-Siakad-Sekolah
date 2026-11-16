<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\AbsensiFilterRequest;
use App\Http\Requests\Api\V1\AbsensiPeriodeRequest;
use App\Http\Requests\Api\V1\JadwalIndexRequest;
use App\Http\Requests\Api\V1\NilaiIndexRequest;
use App\Http\Resources\Api\V1\AbsensiHarianResource;
use App\Http\Resources\Api\V1\AbsensiPerjamResource;
use App\Http\Resources\Api\V1\BerandaResource;
use App\Http\Resources\Api\V1\JadwalResource;
use App\Http\Resources\Api\V1\NilaiKelompokResource;
use App\Http\Resources\Api\V1\RaporDetailResource;
use App\Http\Resources\Api\V1\RaporResource;
use App\Services\BerandaSiswaService;
use App\Services\JadwalSiswaService;
use App\Services\KonteksAkademikService;
use App\Services\RaporSiswaService;
use App\Services\RekapAbsensiService;
use App\Support\ApiResponse;
use App\Support\Hari;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API akademik READ-ONLY untuk role siswa.
 * Identitas siswa selalu dari user yang terautentikasi; tidak ada parameter siswa.
 */
class SiswaController extends \App\Http\Controllers\Controller
{
    private function pastikanSiswa(Request $request)
    {
        try {
            return RaporSiswaService::pastikanSiswa($request->user());
        } catch (DomainException $e) {
            return null;
        }
    }

    // ----------------------------------------------------------------- A. Beranda

    public function beranda(Request $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        return ApiResponse::ok(new BerandaResource(BerandaSiswaService::beranda($request->user())), 'Berhasil');
    }

    // ----------------------------------------------------------------- B. Jadwal

    public function jadwal(JadwalIndexRequest $request): JsonResponse
    {
        return $this->buildJadwal($request, $request->user(), $request->input('tanggal'));
    }

    public function jadwalHariIni(Request $request): JsonResponse
    {
        return $this->buildJadwal($request, $request->user(), now()->format('Y-m-d'));
    }

    private function buildJadwal(Request $request, \App\Models\User $user, ?string $tanggal): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        $konteks = KonteksAkademikService::ambil();
        $jadwalSiswa = app(JadwalSiswaService::class);

        $kelasList = $jadwalSiswa->kelasAktif($siswa->idsiswa, $konteks);
        $kelasIds = $kelasList->pluck('idkelas')->all();

        $hari = null;
        if ($tanggal !== null && $tanggal !== '') {
            $hari = (int) \Carbon\Carbon::parse($tanggal)->dayOfWeekIso;
        }

        $items = $jadwalSiswa->urutkan($jadwalSiswa->jadwal($konteks, $kelasIds, $hari));

        return ApiResponse::ok([
            'tanggal' => $tanggal ?: null,
            'hari' => $hari,
            'nama_hari' => $hari !== null ? Hari::nama($hari) : null,
            'konteks' => $this->konteksPayload($konteks),
            'jadwal' => JadwalResource::collection($items),
        ], 'Berhasil');
    }

    // -------------------------------------------------------------- C/D. Absensi

    public function absensiHarian(AbsensiFilterRequest $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        $konteks = KonteksAkademikService::ambil();
        $rekap = app(RekapAbsensiService::class);

        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $status = $request->input('status');

        // Rekap semester aktif: selalu tanpa filter (sesuai portal).
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

    public function absensiPeriode(AbsensiPeriodeRequest $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
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

    public function absensiPerjam(AbsensiFilterRequest $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        $rekap = app(RekapAbsensiService::class);

        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $status = $request->input('status');

        // Rekap per jam: riwayat lengkap (kanonis jadwal_pertemuan), tanpa filter konteks TA/semester.
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

    // ------------------------------------------------------------------ E. Nilai

    public function nilai(NilaiIndexRequest $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        $semesterId = (int) $request->input('semester_id') ?: null;
        $kelasId = (int) $request->input('kelas_id') ?: null;

        $kelomok = RaporSiswaService::nilaiFinal($request->user(), $semesterId, $kelasId);

        if (($semesterId !== null || $kelasId !== null) && $kelomok->isEmpty()) {
            return ApiResponse::fail('Nilai untuk filter tersebut tidak tersedia.', 404, [], 'NILAI_NOT_FOUND');
        }

        return ApiResponse::ok([
            'rapor' => NilaiKelompokResource::collection($kelomok),
        ], 'Berhasil');
    }

    // ---------------------------------------------------------- F & G. Rapor

    public function rapor(Request $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        try {
            $daftar = RaporSiswaService::daftarRapor($request->user());
        } catch (DomainException $e) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        return ApiResponse::ok([
            'rapor' => RaporResource::collection($daftar),
        ], 'Berhasil');
    }

    public function raporDetail(Request $request, int $semesterId, int $kelasId): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        try {
            $detail = RaporSiswaService::detail($request->user(), $semesterId, $kelasId);
        } catch (DomainException $e) {
            return ApiResponse::fail('Rapor ini tidak tersedia.', 404, [], 'RAPOR_NOT_FOUND');
        }

        return ApiResponse::ok(new RaporDetailResource($detail), 'Berhasil');
    }

    // ----------------------------------------------------------------- Helpers

    private function konteksPayload($konteks): array
    {
        return [
            'valid' => $konteks->valid(),
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
