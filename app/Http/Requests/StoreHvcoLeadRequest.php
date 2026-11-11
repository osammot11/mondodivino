<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHvcoLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')) ?: null,
            'zip' => trim((string) $this->input('zip')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:30'],
            'zip' => [Rule::excludeIf(! config('funnel.collect_zip')), 'nullable', 'string', 'max:20'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'landing_variant' => ['nullable', 'string', Rule::in([config('funnel.landing_variant')])],
            'referrer' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
