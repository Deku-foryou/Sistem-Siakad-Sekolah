<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AbsensiPerjamStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'jadwal_pertemuan_id' => ['required', 'integer', 'min:1'],
            'records' => ['required', 'array', 'min:1', 'max:200'],
            'records.*.siswa_id' => ['required', 'integer', 'min:1'],
            'records.*.status' => ['required', 'in:hadir,sakit,izin,alpha'],
            'records.*.keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
