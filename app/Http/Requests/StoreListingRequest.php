<?php

namespace App\Http\Requests;

use App\Enums\ItemCondition;
use App\Enums\ListingType;
use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'tipe' => ['required', Rule::in([ListingType::Preloved->value])],
        'kondisi' => ['required', Rule::in(['Seperti Baru', 'Baik', 'Cukup', 'Kurang'])],
        'stok' => 'required|integer|min:1',

        'judul' => 'required|string|max:255',
        'kategori_id' => [
            'required',
            Rule::exists('categories', 'id')->where('is_active', true),
            function (string $attribute, mixed $value, Closure $fail): void {

            $berlaku = Category::query()
                ->forListingType(ListingType::Preloved)
                ->whereKey($value)
                ->exists();

            if (! $berlaku) {
                $fail('Kategori tidak berlaku untuk barang bekas.');
            }
},
        ],
        'harga' => ['required', 'integer', 'min:0'],
        'wilayah_id' => 'required|exists:regions,id',
        'hub_id' => [
            'required',
            Rule::exists('hubs', 'id')
                ->where('region_id', $this->wilayah_id)
                ->where('status', 'ACTIVE'),
        ],
        'deskripsi' => 'required|string',

        'fotos' => 'required|array|min:1|max:5',
        'fotos.*' => 'image|mimes:jpeg,png,jpg|max:2048',
    ];
}

    /**
     * Kustomisasi pesan error (opsional tapi disarankan)
     */
    public function messages(): array
    {
        return [
            'fotos.max' => 'Maksimal foto yang dapat diunggah adalah 5 foto.',
            'fotos.*.max' => 'Ukuran setiap foto maksimal adalah 2 MB.',
            'fotos.*.mimes' => 'Format foto harus berupa JPEG atau PNG.',
            'fotos.*.image' => 'File yang diunggah harus berupa gambar.',
        ];
    }
}
