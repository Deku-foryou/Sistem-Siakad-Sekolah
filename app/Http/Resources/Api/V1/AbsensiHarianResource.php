<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Absensi harian milik siswa (satu item).
 */
class AbsensiHarianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'status' => (string) $this->status,
            'catatan' => $this->catatan,
            'kelas' => [
                'id' => $this->kelas?->idkelas,
                'nama' => $this->kelas?->nama,
            ],
        ];
    }
}
