<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Beranda guru: identitas aman + konteks akademik + jadwal hari ini + ringkasan.
 * Sumber dari BerandaGuruService.
 */
class GuruBerandaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;
        $guru = $resource['guru'];
        $konteks = $resource['konteks'];

        return [
            'guru' => [
                'id' => $guru->idguru,
                'nip' => $guru->nip,
                'kode_guru' => $guru->kode_guru,
                'nama' => $guru->nama,
                'status' => $guru->status,
            ],
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
                'sumber' => $k->sumber ?? [],
                'jumlah_siswa' => (int) ($k->jumlah_siswa ?? 0),
            ])->values(),
            'ringkasan' => [
                'jumlah_kelas' => (int) $resource['ringkasan']['jumlah_kelas'],
                'jumlah_penugasan' => (int) $resource['ringkasan']['jumlah_penugasan'],
                'pertemuan_hari_ini' => (int) $resource['ringkasan']['pertemuan_hari_ini'],
                'absensi_perjam_hari_ini' => (int) $resource['ringkasan']['absensi_perjam_hari_ini'],
                'absensi_harian_hari_ini' => (int) $resource['ringkasan']['absensi_harian_hari_ini'],
            ],
            'nama_hari' => $resource['namaHariIni'],
            'jadwal_hari_ini' => GuruJadwalResource::collection($resource['jadwalHariIni']),
        ];
    }
}
