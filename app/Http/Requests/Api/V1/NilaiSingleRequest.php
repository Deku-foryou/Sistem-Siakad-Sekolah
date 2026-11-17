<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Simpan/perbarui satu nilai siswa (tunggal).
 */
class NilaiSingleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'siswa_id' => ['required', 'integer', 'min:1'],
            'komponen_id' => ['required', 'integer', 'min:1'],
            'nilai' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
