<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Gun;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ScheduleEntryRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Enum|Closure>>
     */
    public function rules(): array
    {
        return [
            'ders_atama_id' => ['required', 'integer', 'exists:ders_atamalari,id'],
            'derslik_id' => ['required', 'integer', 'exists:derslikler,id'],
            'gun' => ['required', Rule::enum(Gun::class)],
            'baslangic_saati' => ['required', 'date_format:H:i'],
            'bitis_saati' => [
                'required',
                'date_format:H:i',
                // Zero-padded "HH:MM" values sort lexicographically, so a plain string comparison is exact.
                function (string $attribute, mixed $value, Closure $fail): void {
                    $start = $this->input('baslangic_saati');
                    if (is_string($start) && is_string($value) && $value <= $start) {
                        $fail('Bitiş saati başlangıç saatinden sonra olmalıdır.');
                    }
                },
            ],
        ];
    }
}
