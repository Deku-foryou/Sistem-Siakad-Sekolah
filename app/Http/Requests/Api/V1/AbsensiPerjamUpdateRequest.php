<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AbsensiPerjamUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:hadir,sakit,izin,alpha'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
