<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idtagihan,
            'nama' => $this->nama,
            'nominal' => (int)$this->total_tagihan,
            'terbayar' => PaymentService::terpakaiTagihan($this->resource),
            'sisa' => PaymentService::sisaTagihan($this->resource),
            'status' => $this->status,
            'jatuh_tempo' => $this->tanggal_jatuh_tempo?->format('Y-m-d'),
        ];
    }
}
