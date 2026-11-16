<?php

namespace App\Http\Requests\Admin;

use App\Rules\ActiveMaterial;
use App\Services\ReferencePriceService;
use App\Support\IndonesianFormat;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Terbitkan harga acuan. published_by tidak diambil dari input, tetapi diisi service dari user login.
 */
class StoreReferencePriceRequest extends FormRequest
{
    /**
     * Akses sudah dijaga middleware role:ADMIN di route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sumber harga dirapikan: spasi di awal/akhir dibuang.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('source'))) {
            $this->merge(['source' => trim($this->input('source'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $prices = app(ReferencePriceService::class);

        return [
            'material_id' => ['bail', 'required', 'integer', new ActiveMaterial],
            // Hanya provinsi (region tanpa induk).
            'region_id' => ['bail', 'required', 'integer', Rule::exists('regions', 'id')->whereNull('parent_id')],
            'price_per_kg' => ['bail', 'required', 'integer', 'min:1', 'max:'.ReferencePriceService::MAX_PRICE],
            'effective_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.ReferencePriceService::MIN_EFFECTIVE_DATE,
                'before_or_equal:'.$prices->maxEffectiveDate()->toDateString(),
            ],
            'source' => ['bail', 'required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $prices = app(ReferencePriceService::class);

        return [
            'material_id.required' => __('reference-prices.validation.material_required'),
            'material_id.integer' => __('reference-prices.validation.material_required'),
            // Pesan bawaan ActiveMaterial menyebut "listing baru", jadi diganti untuk form ini.
            'material_id.'.ActiveMaterial::class => __('reference-prices.validation.material_inactive'),
            'region_id.required' => __('reference-prices.validation.region_required'),
            'region_id.integer' => __('reference-prices.validation.region_not_province'),
            'region_id.exists' => __('reference-prices.validation.region_not_province'),
            'price_per_kg.required' => __('reference-prices.validation.price_required'),
            'price_per_kg.integer' => __('reference-prices.validation.price_integer'),
            'price_per_kg.min' => __('reference-prices.validation.price_min', ['min' => IndonesianFormat::rupiah(1)]),
            'price_per_kg.max' => __('reference-prices.validation.price_max', ['max' => IndonesianFormat::rupiah(ReferencePriceService::MAX_PRICE)]),
            'effective_date.required' => __('reference-prices.validation.date_required'),
            'effective_date.date_format' => __('reference-prices.validation.date_format'),
            'effective_date.after_or_equal' => __('reference-prices.validation.date_min', [
                'date' => IndonesianFormat::date(CarbonImmutable::parse(ReferencePriceService::MIN_EFFECTIVE_DATE)),
            ]),
            'effective_date.before_or_equal' => __('reference-prices.validation.date_max', [
                'date' => IndonesianFormat::date($prices->maxEffectiveDate()),
                'days' => ReferencePriceService::MAX_DAYS_AHEAD,
            ]),
            'source.required' => __('reference-prices.validation.source_required'),
            'source.string' => __('reference-prices.validation.source_string'),
            'source.max' => __('reference-prices.validation.source_max', ['max' => 255]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('reference-prices.attributes');
    }
}
