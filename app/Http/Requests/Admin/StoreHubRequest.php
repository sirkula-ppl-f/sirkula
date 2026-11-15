<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'type' => [
                'required',
                Rule::in([
                    'BANK_SAMPAH',
                    'RW',
                    'KOMUNITAS',
                ]),
            ],
            'region_id' => [
                'required',
                'integer',
                Rule::exists('regions', 'id'),
            ],
            'public_address' => [
                'required',
                'string',
                'max:2000',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
