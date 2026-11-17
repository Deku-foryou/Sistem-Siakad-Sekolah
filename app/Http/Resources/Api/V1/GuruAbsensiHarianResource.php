<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu item riwayat absensi harian (guru).
 */
class GuruAbsensiHarianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idabsensi_harian,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'status' => (string) $this->status,
            'catatan' => $this->catatan,
            'kelas' => [
                'id' => $this->kelas?->idkelas,
                'nama' => $this->kelas?->nama,
            ],
            'siswa' => [
                'id' => $this->siswa?->idsiswa,
                'nama' => $this->siswa?->nama,
            ],
        ];
    }
}
