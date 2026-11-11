<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTasteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'mobile_phone' => trim((string) $this->input('mobile_phone')) ?: null,
            'city' => trim((string) $this->input('city')),
            'zip' => strtoupper(trim((string) $this->input('zip'))),
            'state' => strtoupper(trim((string) $this->input('state'))),
            'age_confirmed' => $this->has('age_confirmed')
                ? filter_var($this->input('age_confirmed'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
            'sms_consent' => $this->boolean('sms_consent'),
        ]);
    }

    public function rules(): array
    {
        $adult = $this->input('age_confirmed') === true;
        $requiredForAdult = Rule::requiredIf($adult);
        $options = config('funnel.profile_options');

        return [
            'first_name' => [$requiredForAdult, 'nullable', 'string', 'max:100'],
            'last_name' => [$requiredForAdult, 'nullable', 'string', 'max:100'],
            'mobile_phone' => ['nullable', 'string', 'max:30'],
            'city' => [$requiredForAdult, 'nullable', 'string', 'max:120'],
            'state' => [$requiredForAdult, 'nullable', Rule::in(array_keys(config('funnel.states')))],
            'zip' => [$requiredForAdult, 'nullable', 'string', 'max:20'],
            'age_confirmed' => ['required', 'boolean'],
            'wine_styles' => [$requiredForAdult, 'nullable', 'array', 'min:1', 'max:6'],
            'wine_styles.*' => ['string', Rule::in($options['wine_styles'])],
            'taste_descriptions' => [$requiredForAdult, 'nullable', 'array', 'min:1', 'max:4'],
            'taste_descriptions.*' => ['string', Rule::in($options['taste_descriptions'])],
            'favorite_wines' => ['nullable', 'string', 'max:500'],
            'avoidances' => [$requiredForAdult, 'nullable', 'array', 'min:1', 'max:9'],
            'avoidances.*' => ['string', Rule::in($options['avoidances'])],
            'avoidance_other' => [Rule::requiredIf(in_array('other', (array) $this->input('avoidances'), true)), 'nullable', 'string', 'max:255'],
            'adventure_level' => [$requiredForAdult, 'nullable', Rule::in($options['adventure_levels'])],
            'occasions' => [$requiredForAdult, 'nullable', 'array', 'min:1', 'max:6'],
            'occasions.*' => ['string', Rule::in($options['occasions'])],
            'preferred_mix' => [$requiredForAdult, 'nullable', Rule::in($options['preferred_mixes'])],
            'usual_bottle_spend' => [$requiredForAdult, 'nullable', Rule::in($options['bottle_spend'])],
            'set_budget' => [$requiredForAdult, 'nullable', Rule::in($options['set_budgets'])],
            'desired_timing' => [$requiredForAdult, 'nullable', Rule::in($options['desired_timings'])],
            'italian_memory' => ['nullable', 'string', 'max:2000'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
            'profile_consent' => [$adult ? 'accepted' : 'nullable'],
            'sms_consent' => ['boolean'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'taste_descriptions.max' => 'Choose no more than four taste descriptions.',
            'profile_consent.accepted' => 'Please agree so Luca can use your answers to prepare the consultation.',
        ];
    }
}
