<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class GuruJadwalIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'hari' => ['nullable', 'integer', 'min:1', 'max:7'],
        ];
    }
}
