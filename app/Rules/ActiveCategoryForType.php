<?php

namespace App\Rules;

use App\Enums\ListingType;
use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Tolak category_id yang tidak ada, kategori nonaktif, atau kategori yang tidak berlaku
 * untuk tipe listing yang diberikan.
 *
 * Contoh di FormRequest listing baru (PBI-07/08):
 *
 *     public function rules(): array
 *     {
 *         return [
 *             'category_id' => ['required', 'integer', new ActiveCategoryForType(ListingType::Preloved)],
 *             'material_id' => ['required', 'integer', new ActiveMaterial],
 *         ];
 *     }
 *
 * Bila tipe listing dipilih di form yang sama, validasi tipe dulu lalu buat rule dari
 * ListingType::tryFrom($this->input('listing_type')) (jangan from(), karena input bisa tidak valid).
 */
class ActiveCategoryForType implements ValidationRule
{
    public function __construct(private ListingType $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Nilai yang bukan bilangan bulat positif dianggap tidak ditemukan.
        $isId = is_int($value) || (is_string($value) && ctype_digit($value));
        $category = $isId ? Category::query()->find((int) $value) : null;

        if ($category === null) {
            $fail('validation.active_category.missing')->translate();

            return;
        }

        if (! $category->is_active) {
            $fail('validation.active_category.inactive')->translate();

            return;
        }

        if (! $category->listingTypes()->contains($this->type)) {
            $fail('validation.active_category.type')->translate(['type' => $this->type->label()]);
        }
    }
}
