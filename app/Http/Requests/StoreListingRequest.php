<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'listing_type' => ['required', Rule::enum(ListingType::class)]
            'title' => 'required|string|max:255',
            'condition' => 'required|string',
            'price' => 'required|integer|min:1',
            'stock' => 'required|integer|min:1',
            'hub_id' => 'required|exists:hubs,id',
            'region_id' => 'required|exists:regions,id',
            'description' => 'required|string',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'image|mimes:jpeg,png|max:2048',
            'action' => 'required|in:draft,submit',
        ];

        if ($this->listing_type === 'PRELOVED') {
            $rules['category_id'] = 'required|exists:categories,id';
        } elseif ($this->listing_type === 'RECYCLABLE') {
            $rules['material_id'] = 'required|exists:materials,id';
            $rules['weight_per_lot'] = 'required|numeric|min:0.1';
        } elseif ($this->listing_type === 'UPCYCLE') {
            $rules['storefront_id'] = 'required|exists:storefronts,id';
            $rules['category_id'] = 'required|exists:categories,id';
            $rules['weight_per_lot'] = 'nullable|numeric|min:0.1';
        }

        
        return $rules;
    }
}