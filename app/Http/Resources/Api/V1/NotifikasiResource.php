<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Notifikasi milik user yang login. Baris penerima milik user lain tidak pernah diekspos.
 * Sumber referenced (sumber_tabel/sumber_id/link) hanya path internal yang aman.
 */
class NotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $penerima = $this->penerima->first();

        return [
            'id' => $this->idnotifikasi,
            'judul' => $this->judul,
            'isi' => $this->isi,
            'jenis' => $this->jenis,
            'prioritas' => $this->prioritas,
            'waktu_dibuat' => $this->waktu_dibuat?->toIso8601String(),
            'sumber_tabel' => $this->sumber_tabel,
            'sumber_id' => $this->sumber_id,
            'link' => $this->link,
            'dibaca' => $penerima?->dibaca_pada !== null,
            'dibaca_pada' => $penerima?->dibaca_pada?->toIso8601String(),
        ];
    }
}
