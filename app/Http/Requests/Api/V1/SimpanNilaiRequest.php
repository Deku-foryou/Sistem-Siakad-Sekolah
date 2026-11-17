<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Batch simpan nilai: { "nilai": { "<siswa_id>": { "<komponen_id>": <nilai> } } }
 * Nilai kosong (null/'') dilewati oleh NilaiSiswaService::simpanBanyak.
 */
class SimpanNilaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nilai' => ['required', 'array', 'min:1'],
            'nilai.*' => ['required', 'array'],
            'nilai.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
