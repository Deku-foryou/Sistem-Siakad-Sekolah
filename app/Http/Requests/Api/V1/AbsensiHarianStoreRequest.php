<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AbsensiHarianStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Tahun ajaran/semester TIDAK diterima dari client; selalu dari konteks aktif server.
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'kelas_id' => ['required', 'integer', 'min:1'],
            'records' => ['required', 'array', 'min:1', 'max:200'],
            'records.*.siswa_id' => ['required', 'integer', 'min:1'],
            'records.*.status' => ['required', 'in:hadir,sakit,izin,alpha'],
            'records.*.catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
