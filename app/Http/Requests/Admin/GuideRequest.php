<?php

namespace App\Http\Requests\Admin;

use App\Models\Guide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuideRequest extends FormRequest
{
    // Akses admin sudah dijaga middleware route (auth, active, role:ADMIN).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'status' => ['required', Rule::in(Guide::STATUSES)],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*' => ['string', Rule::in(Guide::PLACEMENTS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => mb_strtolower(__('guides.form.title')),
            'body' => mb_strtolower(__('guides.form.body')),
            'status' => mb_strtolower(__('guides.form.status')),
            'placements' => mb_strtolower(__('guides.form.placements')),
        ];
    }

    public function messages(): array
    {
        return [
            'placements.required' => __('guides.validation.placements_required'),
            'placements.min' => __('guides.validation.placements_required'),
            'placements.*.in' => __('guides.validation.placement_invalid'),
        ];
    }
}
