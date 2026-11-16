<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NilaiIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'semester_id' => ['nullable', 'integer', 'min:1', Rule::exists('semester', 'idsemester')],
            'kelas_id' => ['nullable', 'integer', 'min:1', Rule::exists('kelas', 'idkelas')],
        ];
    }
}
