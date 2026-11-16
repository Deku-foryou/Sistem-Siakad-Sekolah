<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Hari;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu item jadwal (jadwalkelas + jamhari + mapel + guru).
 * Tidak memuat data internal sekolah lain.
 */
class JadwalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $jamhari = $this->jamhari;
        $h = (int) ($jamhari?->hari ?? 0);

        return [
            'hari' => $h,
            'nama_hari' => Hari::nama($h),
            'jam_ke' => $jamhari?->jam_ke,
            'mulai' => $this->jam($jamhari?->mulai),
            'selesai' => $this->jam($jamhari?->selesai),
            'mapel' => [
                'id' => $this->mapel?->idmapel,
                'kode' => $this->mapel?->kode,
                'nama' => $this->mapel?->nama,
            ],
            'guru' => [
                'id' => $this->guru?->idguru,
                'nama' => $this->guru?->nama,
            ],
            'kelas_id' => $jamhari?->kelas_id,
        ];
    }

    private function jam(mixed $waktu): ?string
    {
        $s = substr((string) $waktu, 0, 5);

        return $s === '' ? null : $s;
    }
}
