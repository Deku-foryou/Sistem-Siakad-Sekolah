<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Penugasan mengajar milik guru (semester aktif).
 */
class PenugasanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idpenugasan_mengajar,
            'semester' => [
                'id' => $this->semester?->idsemester,
                'nama' => $this->semester?->nama,
                'tahun_ajaran' => $this->semester?->tahunajaran?->nama,
            ],
            'kelas' => [
                'id' => $this->kelas?->idkelas,
                'nama' => $this->kelas?->nama,
                'tingkat' => $this->kelas?->tingkat,
            ],
            'mapel' => [
                'id' => $this->mapel?->idmapel,
                'kode' => $this->mapel?->kode,
                'nama' => $this->mapel?->nama,
            ],
        ];
    }
}
