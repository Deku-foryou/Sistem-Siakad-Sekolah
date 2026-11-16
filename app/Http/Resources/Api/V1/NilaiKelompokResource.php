<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kelompok nilai akhir (snapshot finalisasi) per rapor — untuk endpoint /siswa/nilai.
 * Hanya rapor final; nilai draft tidak pernah tampil di sini.
 */
class NilaiKelompokResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        return [
            'semester' => [
                'id' => $resource['semester']->idsemester,
                'nama' => $resource['semester']->nama,
                'tahunajaran' => [
                    'id' => $resource['semester']->tahunajaran?->idtahunajaran,
                    'nama' => $resource['semester']->tahunajaran?->nama,
                ],
            ],
            'kelas' => [
                'id' => $resource['kelas']->idkelas,
                'nama' => $resource['kelas']->nama,
                'tingkat' => $resource['kelas']->tingkat,
            ],
            'nilai' => $resource['nilai']->values(),
        ];
    }
}
