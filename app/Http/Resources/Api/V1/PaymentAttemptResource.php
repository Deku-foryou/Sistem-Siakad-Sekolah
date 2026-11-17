<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idpayment_attempt,
            'order_id' => $this->order_id,
            'nominal' => (int)$this->nominal,
            'status' => $this->status,
            'snap_token' => $this->snap_token,
            'snap_redirect_url' => $this->snap_redirect_url,
        ];
    }
}
