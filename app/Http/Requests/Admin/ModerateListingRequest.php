<?php

namespace App\Http\Requests\Admin;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alasan untuk menonaktifkan atau memulihkan listing (PBI-06). Admin pelaku diambil dari user login,
 * bukan dari input.
 */
class ModerateListingRequest extends FormRequest
{
    public const REASON_MIN = 10;

    public const REASON_MAX = 500;

    /**
     * Akses sudah dijaga middleware role:ADMIN di route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Alasan dirapikan: spasi di awal/akhir dibuang.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reason'))) {
            $this->merge(['reason' => trim($this->input('reason'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['bail', 'required', 'string', 'min:'.self::REASON_MIN, 'max:'.self::REASON_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => __('listing-moderation.validation.reason_required'),
            'reason.string' => __('listing-moderation.validation.reason_string'),
            'reason.min' => __('listing-moderation.validation.reason_min', ['min' => self::REASON_MIN]),
            'reason.max' => __('listing-moderation.validation.reason_max', ['max' => self::REASON_MAX]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('listing-moderation.attributes');
    }

    /**
     * Validasi gagal selalu kembali ke halaman detail listing, tempat form berada.
     */
    protected function getRedirectUrl(): string
    {
        $listing = $this->route('listing');

        return $listing instanceof Listing
            ? route('admin.listings.show', $listing)
            : parent::getRedirectUrl();
    }
}
