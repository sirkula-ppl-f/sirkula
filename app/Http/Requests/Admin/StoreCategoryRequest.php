<?php

namespace App\Http\Requests\Admin;

use App\Enums\ListingType;
use App\Rules\UniqueCaseInsensitive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Akses sudah dijaga middleware role:ADMIN di route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rapikan nama: spasi di awal/akhir dibuang dan spasi ganda dijadikan satu.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => Str::squish($this->input('name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', new UniqueCaseInsensitive('categories', 'name', $this->ignoredCategoryId())],
            'description' => ['nullable', 'string', 'max:500'],
            'listing_types' => ['required', 'array', 'min:1'],
            'listing_types.*' => [Rule::enum(ListingType::class)],
        ];
    }

    /**
     * Id kategori yang diabaikan saat memeriksa nama unik. Diisi oleh request ubah.
     */
    protected function ignoredCategoryId(): ?int
    {
        return null;
    }
}
