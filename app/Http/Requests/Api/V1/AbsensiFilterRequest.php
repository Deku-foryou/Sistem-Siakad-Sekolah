<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filter tanggal/status + pagination untuk histori absensi (harian & per jam).
 */
class AbsensiFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:hadir,sakit,izin,alpha'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function ($validator) {
                $dari = $this->input('dari');
                $sampai = $this->input('sampai');

                if ($dari && $sampai && $dari > $sampai) {
                    $validator->errors()->add('dari', 'Tanggal awal tidak boleh melebihi tanggal akhir.');
                }
            },
        ];
    }
}
