<?php

namespace App\Rules;

use App\Models\Material;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Tolak material_id yang tidak ada atau material nonaktif.
 *
 * Contoh di FormRequest listing baru (PBI-07/08):
 *
 *     'material_id' => ['required', 'integer', new ActiveMaterial],
 */
class ActiveMaterial implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Nilai yang bukan bilangan bulat positif dianggap tidak ditemukan.
        $isId = is_int($value) || (is_string($value) && ctype_digit($value));
        $material = $isId ? Material::query()->find((int) $value) : null;

        if ($material === null) {
            $fail('validation.active_material.missing')->translate();

            return;
        }

        if (! $material->is_active) {
            $fail('validation.active_material.inactive')->translate();
        }
    }
}
