<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'akademik_donem_id' => ['required', 'integer', 'exists:akademik_donemler,id'],
            'ders_id' => ['required', 'integer', 'exists:dersler,id'],
            'ogretim_elemani_id' => ['required', 'integer', 'exists:ogretim_elemanlari,id'],
            'haftalik_saat' => ['nullable', 'integer', 'min:1', 'max:40'],
            'sube' => ['nullable', 'string', 'max:10', 'alpha_num'],
        ];
    }
}
