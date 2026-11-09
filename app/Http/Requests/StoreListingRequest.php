<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Aturan spesifik untuk PBI-07
            'tipe' => 'required|in:PRELOVED',
            'kondisi' => 'required|string',
            'stok' => 'required|integer|min:1',
            
            // Aturan standar form listing
            'judul' => 'required|string|max:255',
            'kategori_id' => 'required|exists:categories,id',
            'harga' => 'required|numeric|min:0',
            'hub_id' => 'required|exists:hubs,id',
            'wilayah_id' => 'required|exists:regions,id',
            'deskripsi' => 'required|string',

            // Aturan untuk array foto
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