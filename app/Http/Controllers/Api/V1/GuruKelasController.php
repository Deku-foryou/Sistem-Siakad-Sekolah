<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\PenugasanResource;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Services\GuruApiService;
use App\Services\KonteksAkademikService;
use App\Services\NilaiSiswaService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API kelas & penugasan guru.
 * Hanya kelas/penugasan yang secara sah terhubung dengan guru yang login.
 */
class GuruKelasController extends \App\Http\Controllers\Controller
{
    private function pastikanGuru(Request $request)
    {
        try {
            return GuruApiService::pastikanGuru($request->user());
        } catch (DomainException) {
            return null;
        }
    }

    public function index(Request $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return ApiResponse::fail('Akun guru ini belum terhubung ke data guru atau nonaktif. Hubungi administrator.', 403, [], 'GURU_NOT_LINKED');
        }

        $konteks = KonteksAkademikService::ambil();
        $sumber = GuruApiService::sumberKelas($guru, $konteks);
        $ids = array_keys($sumber);

        if ($ids === []) {
            return ApiResponse::ok(['konteks' => GuruApiService::konteksPayload($konteks), 'kelas' => []], 'Berhasil');
        }

        $kelasList = Kelas::query()
            ->with(['tahunajaran:idtahunajaran,nama', 'jurusan:idjurusan,nama'])
            ->withCount(['kelassiswa as jumlah_siswa' => fn ($query) => $query->where('status', 'aktif')])
            ->whereIn('idkelas', $ids)
            ->orderBy('nama')
            ->get();

        // Mapel yang diampu guru per kelas (jadwalkelas + penugasan_mengajar, semester aktif).
        $mapelPerKelas = $this->mapelPerKelas($guru, $konteks->semester?->idsemester);

        $payload = $kelasList->map(fn (Kelas $kelas) => [
            'id' => $kelas->idkelas,
            'nama' => $kelas->nama,
            'tingkat' => $kelas->tingkat,
            'jenis' => $kelas->jenis,
            'rombel' => $kelas->rombel,
            'tahunajaran' => $kelas->tahunajaran ? ['id' => $kelas->tahunajaran->idtahunajaran, 'nama' => $kelas->tahunajaran->nama] : null,
            'jurusan' => $kelas->jurusan ? ['id' => $kelas->jurusan->idjurusan, 'nama' => $kelas->jurusan->nama] : null,
            'sumber' => $sumber[(int) $kelas->idkelas] ?? [],
            'jumlah_siswa' => (int) ($kelas->jumlah_siswa ?? 0),
            'mapel' => $mapelPerKelas[(int) $kelas->idkelas] ?? [],
        ])->values();

        return ApiResponse::ok([
            'konteks' => GuruApiService::konteksPayload($konteks),
            'kelas' => $payload,
        ], 'Berhasil');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();
        $kelas = GuruApiService::ambilKelasBerwenang($guru, $id, $konteks);

        if (! $kelas) {
            return ApiResponse::fail('Kelas tidak ditemukan.', 404, [], 'KELAS_NOT_FOUND');
        }

        $sumber = GuruApiService::sumberKelas($guru, $konteks)[(int) $kelas->idkelas] ?? [];
        $mapelPerKelas = $this->mapelPerKelas($guru, $konteks->semester?->idsemester);

        // Penugasan mengajar guru pada kelas ini (semester aktif).
        $penugasan = [];
        try {
            $penugasan = NilaiSiswaService::daftarPenugasan($request->user())
                ->where('kelas_idkelas', (int) $kelas->idkelas)
                ->values()
                ->map(fn ($p) => [
                    'id' => $p->idpenugasan_mengajar,
                    'mapel' => ['id' => $p->mapel?->idmapel, 'kode' => $p->mapel?->kode, 'nama' => $p->mapel?->nama],
                ])->all();
        } catch (DomainException) {
            $penugasan = [];
        }

        return ApiResponse::ok([
            'id' => $kelas->idkelas,
            'nama' => $kelas->nama,
            'tingkat' => $kelas->tingkat,
            'jenis' => $kelas->jenis,
            'rombel' => $kelas->rombel,
            'tahunajaran' => $kelas->tahunajaran ? ['id' => $kelas->tahunajaran->idtahunajaran, 'nama' => $kelas->tahunajaran->nama] : null,
            'jurusan' => $kelas->jurusan ? ['id' => $kelas->jurusan->idjurusan, 'nama' => $kelas->jurusan->nama] : null,
            'wali_kelas' => $kelas->wali ? ['id' => $kelas->wali->idguru, 'nama' => $kelas->wali->nama] : null,
            'sumber' => $sumber,
            'jumlah_siswa' => (int) ($kelas->jumlah_siswa ?? 0),
            'mapel' => $mapelPerKelas[(int) $kelas->idkelas] ?? [],
            'penugasan' => $penugasan,
        ], 'Berhasil');
    }

    public function siswa(Request $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();
        $kelas = GuruApiService::ambilKelasBerwenang($guru, $id, $konteks);

        if (! $kelas) {
            return ApiResponse::fail('Kelas tidak ditemukan.', 404, [], 'KELAS_NOT_FOUND');
        }

        $siswa = Kelassiswa::query()
            ->where('kelas_idkelas', $kelas->idkelas)
            ->where('status', 'aktif')
            ->whereHas('siswa', fn ($query) => $query->where('status', 'aktif'))
            ->with('siswa:idsiswa,nisn,nis,nama,jenis_kelamin,status')
            ->orderBy('siswa_idsiswa')
            ->get()
            ->map(fn ($ks) => [
                'id' => $ks->siswa?->idsiswa,
                'nisn' => $ks->siswa?->nisn,
                'nis' => $ks->siswa?->nis,
                'nama' => $ks->siswa?->nama,
                'jenis_kelamin' => $ks->siswa?->jenis_kelamin,
            ])
            ->values();

        return ApiResponse::ok([
            'kelas' => ['id' => $kelas->idkelas, 'nama' => $kelas->nama],
            'jumlah' => $siswa->count(),
            'siswa' => $siswa,
        ], 'Berhasil');
    }

    public function penugasan(Request $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        try {
            $daftar = NilaiSiswaService::daftarPenugasan($request->user());
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'KONTEKS_TIDAK_VALID');
        }

        return ApiResponse::ok([
            'penugasan' => PenugasanResource::collection($daftar),
        ], 'Berhasil');
    }

    /** @return array<int, array> */
    private function mapelPerKelas($guru, ?int $semesterId): array
    {
        if (! $semesterId) {
            return [];
        }

        $baris = \Illuminate\Support\Facades\DB::table('jadwalkelas as j')
            ->join('jamhari as jh', 'jh.idjamhari', '=', 'j.jamhari_idjamhari')
            ->join('mapel as m', 'm.idmapel', '=', 'j.mapel_idmapel')
            ->where('j.semester_idsemester', $semesterId)
            ->where('j.guru_idguru', $guru->idguru)
            ->where('jh.jenis', 'pelajaran')
            ->select('jh.kelas_id', 'm.idmapel', 'm.kode', 'm.nama')
            ->distinct()
            ->get();

        $hasil = [];
        foreach ($baris as $row) {
            $hasil[(int) $row->kelas_id][] = ['id' => (int) $row->idmapel, 'kode' => $row->kode, 'nama' => $row->nama];
        }

        return $hasil;
    }

    private function gagalGuru(): JsonResponse
    {
        return ApiResponse::fail('Akun guru ini belum terhubung ke data guru atau nonaktif. Hubungi administrator.', 403, [], 'GURU_NOT_LINKED');
    }
}
