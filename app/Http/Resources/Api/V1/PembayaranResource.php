<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PembayaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idpembayaran,
            'nominal' => (int)$this->nominal_dibayar,
            'metode' => $this->metode,
            'status' => $this->status,
            'dibayar_pada' => $this->dibayar_pada?->toIso8601String(),
        ];
    }
}
