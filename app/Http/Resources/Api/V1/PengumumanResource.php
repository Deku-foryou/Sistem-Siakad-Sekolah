<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pengumuman untuk siswa. Snapshot penerima TIDAK diekspos; target hanya diwariskan
 * bila memang aman ditampilkan client.
 */
class PengumumanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baca = $this->baca->first();

        return [
            'id' => $this->idpengumuman,
            'judul' => $this->judul,
            'isi' => $this->isi,
            'kategori' => $this->kategori,
            'prioritas' => $this->prioritas,
            'waktu_terbit' => $this->waktu_terbit?->toIso8601String(),
            'berlaku_dari' => $this->berlaku_dari?->format('Y-m-d'),
            'berlaku_sampai' => $this->berlaku_sampai?->format('Y-m-d'),
            'dibaca' => $baca?->dibaca_pada !== null,
            'dibaca_pada' => $baca?->dibaca_pada?->toIso8601String(),
        ];
    }
}
