<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Beranda siswa: identitas aman + konteks akademik + jadwal hari ini + kelas aktif.
 * Sumber dari BerandaSiswaService (identik dengan portal /berandasiswa).
 */
class BerandaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;
        $siswa = $resource['siswa'];
        $konteks = $resource['konteks'];

        return [
            'siswa' => $siswa ? [
                'id' => $siswa->idsiswa,
                'nisn' => $siswa->nisn,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'tahun_masuk' => $siswa->tahun_masuk,
                'status' => $siswa->status,
            ] : null,
            'akun' => [
                'username' => $resource['akun']['username'],
                'wajib_ganti_password' => $resource['akun']['wajibGantiPassword'],
            ],
            'konteks' => [
                'valid' => $konteks->valid(),
                'peringatan' => $konteks->pesanPeringatan(),
                'tahun_ajaran' => $konteks->tahunAjaran ? [
                    'id' => $konteks->tahunAjaran->idtahunajaran,
                    'nama' => $konteks->tahunAjaran->nama,
                ] : null,
                'semester' => $konteks->semester ? [
                    'id' => $konteks->semester->idsemester,
                    'nama' => $konteks->semester->nama,
                ] : null,
            ],
            'kelas' => $resource['kelasList']->map(fn ($k) => [
                'id' => $k->idkelas,
                'nama' => $k->nama,
                'tingkat' => $k->tingkat,
                'jenis' => $k->jenis,
                'rombel' => $k->rombel,
            ])->values(),
            'nama_hari' => $resource['namaHariIni'],
            'jadwal_hari_ini' => JadwalResource::collection($resource['jadwalHariIni']),
        ];
    }
}
