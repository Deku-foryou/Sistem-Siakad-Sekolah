<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu entri daftar rapor final siswa: semester + kelas + ringkasan finalisasi.
 */
class RaporResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $semester = $this->resource['semester'];
        $kelas = $this->resource['kelas'];
        $finalisasi = $this->resource['finalisasi'];

        return [
            'semester' => [
                'id' => $semester->idsemester,
                'nama' => $semester->nama,
                'tahunajaran' => [
                    'id' => $semester->tahunajaran?->idtahunajaran,
                    'nama' => $semester->tahunajaran?->nama,
                ],
            ],
            'kelas' => [
                'id' => $kelas->idkelas,
                'nama' => $kelas->nama,
                'tingkat' => $kelas->tingkat,
            ],
            'wali_kelas' => $finalisasi->waliKelas?->nama,
            'difinalisasi_pada' => $finalisasi->difinalisasi_pada?->toIso8601String(),
        ];
    }
}
