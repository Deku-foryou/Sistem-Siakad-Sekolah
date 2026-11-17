<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AbsensiHarianUpdateRequest extends FormRequest
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
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
