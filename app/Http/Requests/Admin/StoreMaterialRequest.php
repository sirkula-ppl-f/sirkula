<?php

namespace App\Http\Requests\Admin;

use App\Rules\UniqueCaseInsensitive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreMaterialRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', new UniqueCaseInsensitive('materials', 'name', $this->ignoredMaterialId())],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Id material yang diabaikan saat memeriksa nama unik. Diisi oleh request ubah.
     */
    protected function ignoredMaterialId(): ?int
    {
        return null;
    }
}
