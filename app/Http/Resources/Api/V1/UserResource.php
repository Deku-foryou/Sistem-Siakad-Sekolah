<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload user yang aman untuk client mobile.
 * Tidak pernah memuat password, hash, remember_token, atau data internal lain.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'level' => $this->level,
            'status_akun' => $this->status_akun,
            'wajib_ganti_password' => (bool) $this->wajib_ganti_password,
        ];
    }
}
