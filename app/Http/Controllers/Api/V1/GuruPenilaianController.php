<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\NilaiSingleRequest;
use App\Http\Requests\Api\V1\SimpanNilaiRequest;
use App\Services\GuruApiService;
use App\Services\NilaiSiswaService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API penilaian guru.
 * Sumber otorisasi tunggal: penugasan_mengajar via NilaiSiswaService.
 * Komponen/bobot hanya dibaca (konfigurasi = kewenangan superadmin).
 */
class GuruPenilaianController extends \App\Http\Controllers\Controller
{
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

    /**
     * Ambil penugasan milik guru; 404 bila tidak ada / bukan miliknya (anti-IDOR).
     */
    private function penugasanMilikGuru(Request $request, int $id)
    {
        try {
            return NilaiSiswaService::penugasan($request->user(), $id);
        } catch (DomainException) {
            return null;
        }
    }

    /**
     * GET /api/v1/guru/penilaian/penugasan/{id} — detail penugasan + komponen & bobot + status kunci.
     */
    public function penugasanDetail(Request $request, int $id): JsonResponse
    {
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
        }

        $penugasan = $this->penugasanMilikGuru($request, $id);
        if (! $penugasan) {
            return ApiResponse::fail('Penugasan mengajar tidak ditemukan.', 404, [], 'PENUGASAN_NOT_FOUND');
        }

        $komponen = NilaiSiswaService::komponenAktif($penugasan);
        $konfigurasiValid = NilaiSiswaService::konfigurasiValid($penugasan);
        $terkunci = NilaiSiswaService::terkunci($penugasan);
        $siswa = NilaiSiswaService::siswaPenugasan($penugasan);

        return ApiResponse::ok([
            'id' => $penugasan->idpenugasan_mengajar,
            'semester' => [
                'id' => $penugasan->semester?->idsemester,
                'nama' => $penugasan->semester?->nama,
                'tahun_ajaran' => $penugasan->semester?->tahunajaran?->nama,
            ],
            'kelas' => [
                'id' => $penugasan->kelas?->idkelas,
                'nama' => $penugasan->kelas?->nama,
                'tingkat' => $penugasan->kelas?->tingkat,
            ],
            'mapel' => [
                'id' => $penugasan->mapel?->idmapel,
                'kode' => $penugasan->mapel?->kode,
                'nama' => $penugasan->mapel?->nama,
            ],
            'jumlah_siswa' => $siswa->count(),
            'komponen' => $komponen->map(fn ($item) => [
                'id' => $item->idkomponen_nilai,
                'nama' => $item->nama,
                'bobot' => (float) $item->bobot,
                'urutan' => (int) $item->urutan,
            ])->values(),
            'total_bobot_aktif' => round((float) $komponen->sum(fn ($item) => (float) $item->bobot), 2),
            'konfigurasi_valid' => $konfigurasiValid,
            'terkunci' => $terkunci,
        ], 'Berhasil');
    }

    /**
     * GET /api/v1/guru/penilaian/penugasan/{id}/siswa — daftar siswa + nilai tersimpan + nilai draft (rekap).
     */
    public function penugasanSiswa(Request $request, int $id): JsonResponse
    {
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
        }

        $penugasan = $this->penugasanMilikGuru($request, $id);
        if (! $penugasan) {
            return ApiResponse::fail('Penugasan mengajar tidak ditemukan.', 404, [], 'PENUGASAN_NOT_FOUND');
        }

        $rekap = NilaiSiswaService::rekap($penugasan);

        $items = $rekap->map(fn ($baris) => [
            'id' => $baris['siswa']->idsiswa,
            'nisn' => $baris['siswa']->nisn,
            'nis' => $baris['siswa']->nis,
            'nama' => $baris['siswa']->nama,
            'nilai' => (object) $baris['nilai'],
            'nilai_draft' => $baris['nilai_draft'],
            'lengkap' => $baris['lengkap'],
            'jumlah_terisi' => $baris['jumlah_terisi'],
            'jumlah_komponen' => $baris['jumlah_komponen'],
            'bobot_terisi' => $baris['bobot_terisi'],
            'bobot_total' => $baris['bobot_total'],
            'konfigurasi_valid' => $baris['konfigurasi_valid'],
        ])->values();

        return ApiResponse::ok([
            'penugasan_id' => $penugasan->idpenugasan_mengajar,
            'terkunci' => NilaiSiswaService::terkunci($penugasan),
            'siswa' => $items,
        ], 'Berhasil');
    }

    /**
     * PUT /api/v1/guru/penilaian/penugasan/{id}/nilai — batch simpan nilai (upsert).
     * Payload: { "nilai": { "<siswa_id>": { "<komponen_id>": <nilai> } } }
     * Nilai kosong (null/'') dilewati, bukan menghapus nilai tersimpan.
     */
    public function simpanBanyak(SimpanNilaiRequest $request, int $id): JsonResponse
    {
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
        }

        try {
            $jumlah = NilaiSiswaService::simpanBanyak(
                $request->user(),
                $id,
                $request->input('nilai')
            );
        } catch (DomainException $e) {
            $pesan = $e->getMessage();
            $status = str_contains($pesan, 'bukan milik Anda') || str_contains($pesan, 'tidak ditemukan') ? 404 : 422;

            return ApiResponse::fail($pesan, $status, [], $status === 404 ? 'PENUGASAN_NOT_FOUND' : 'GAGAL_SIMPAN');
        }

        return ApiResponse::ok([
            'tersimpan' => $jumlah,
        ], $jumlah > 0 ? $jumlah.' nilai berhasil disimpan.' : 'Tidak ada nilai yang diisi, sehingga tidak ada yang disimpan.');
    }

    /**
     * PATCH /api/v1/guru/penilaian/penugasan/{id}/nilai — simpan/perbarui satu nilai.
     * Payload: { "siswa_id": int, "komponen_id": int, "nilai": 0-100 }
     */
    public function simpanSatu(NilaiSingleRequest $request, int $id): JsonResponse
    {
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
        }

        try {
            $nilai = NilaiSiswaService::simpan(
                $request->user(),
                $id,
                (int) $request->input('siswa_id'),
                (int) $request->input('komponen_id'),
                $request->input('nilai')
            );
        } catch (DomainException $e) {
            $pesan = $e->getMessage();
            $status = str_contains($pesan, 'bukan milik Anda') || str_contains($pesan, 'tidak ditemukan') ? 404 : 422;

            return ApiResponse::fail($pesan, $status, [], $status === 404 ? 'PENUGASAN_NOT_FOUND' : 'GAGAL_SIMPAN');
        }

        return ApiResponse::ok([
            'id' => $nilai->idnilai_siswa,
            'siswa_id' => (int) $nilai->siswa_idsiswa,
            'komponen_id' => (int) $nilai->komponen_nilai_idkomponen_nilai,
            'nilai' => (float) $nilai->nilai,
        ], 'Nilai berhasil disimpan.');
    }
}
