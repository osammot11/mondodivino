<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\HvcoLead;
use App\Models\TasteProfile;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoClient
{
    public function upsertContact(HvcoLead $lead): void
    {
        $listId = $this->requiredInteger('list_id');

        $values = [
            'first_name' => $lead->first_name,
            'phone' => $lead->phone,
            'zip' => $lead->zip,
            'funnel_status' => $lead->funnel_status->value,
            'utm_source' => $lead->utm_source,
            'utm_medium' => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            'utm_content' => $lead->utm_content,
            'utm_term' => $lead->utm_term,
            'referrer' => $lead->referrer,
            'landing_variant' => $lead->landing_variant,
            'consent_at' => $lead->consent_at->toIso8601String(),
            'locale' => $lead->locale,
        ];
        $attributes = [];

        foreach (config('services.brevo.attributes', []) as $property => $brevoAttribute) {
            if (is_string($brevoAttribute) && $brevoAttribute !== '' && isset($values[$property])) {
                $attributes[$brevoAttribute] = $values[$property];
            }
        }

        $this->request()->post('/contacts', [
            'email' => $lead->email,
            'attributes' => $attributes,
            'listIds' => [$listId],
            'updateEnabled' => true,
        ])->throw();
    }

    public function trackOptin(HvcoLead $lead): void
    {
        $this->request()->post('/events', [
            'event_name' => 'hvco_optin',
            'identifiers' => ['email_id' => $lead->email],
            'event_properties' => ['landing_variant' => $lead->landing_variant],
        ])->throw();
    }

    public function sendGuide(HvcoLead $lead, string $profileUrl): void
    {
        $payload = [
            'templateId' => $this->requiredInteger('hvco_template_id'),
            'to' => [['email' => $lead->email, 'name' => $lead->first_name]],
            'params' => [
                'first_name' => $lead->first_name,
                'guide_url' => $this->requiredString('hvco_guide_url'),
                'profile_url' => $profileUrl,
            ],
        ];

        if ($email = config('services.brevo.sender_email')) {
            $payload['sender'] = ['email' => $email, 'name' => config('services.brevo.sender_name')];
        }

        $this->request()->post('/smtp/email', $payload)->throw();
    }

    public function upsertTasteProfile(TasteProfile $profile): void
    {
        $lead = $profile->lead;
        $values = [
            'first_name' => $lead->first_name,
            'funnel_status' => $lead->funnel_status->value,
            'state' => $profile->state,
            'adventure_level' => $profile->adventure_level,
            'usual_bottle_spend' => $profile->usual_bottle_spend,
            'set_budget' => $profile->set_budget,
            'desired_timing' => $profile->desired_timing,
            'completed_at' => $profile->completed_at?->toIso8601String(),
        ];
        $attributes = [];

        foreach (config('services.brevo.profile_attributes', []) as $property => $brevoAttribute) {
            if (is_string($brevoAttribute) && $brevoAttribute !== '' && isset($values[$property])) {
                $attributes[$brevoAttribute] = $values[$property];
            }
        }

        $this->request()->post('/contacts', [
            'email' => $lead->email,
            'attributes' => $attributes,
            'listIds' => [$this->requiredInteger('list_id')],
            'updateEnabled' => true,
        ])->throw();
    }

    public function trackTasteProfileCompleted(TasteProfile $profile): void
    {
        $this->request()->post('/events', [
            'event_name' => 'taste_profile_completed',
            'identifiers' => ['email_id' => $profile->lead->email],
            'event_properties' => [
                'state' => $profile->state,
                'adventure_level' => $profile->adventure_level,
                'set_budget' => $profile->set_budget,
            ],
        ])->throw();
    }

    public function syncBooking(Booking $booking, string $eventName, string $eventKey): void
    {
        $lead = $booking->lead;
        $values = [
            'funnel_status' => $lead->funnel_status->value,
            'booking_status' => $booking->status->value,
            'call_start_utc' => $booking->starts_at?->utc()->toIso8601String(),
            'call_timezone' => $booking->timezone,
            'calcom_booking_uid' => $booking->provider_booking_uid,
        ];
        $attributes = [];
        foreach (config('services.brevo.booking_attributes', []) as $property => $brevoAttribute) {
            if (is_string($brevoAttribute) && $brevoAttribute !== '' && isset($values[$property])) {
                $attributes[$brevoAttribute] = $values[$property];
            }
        }

        $this->request()->post('/contacts', [
            'email' => $lead->email, 'attributes' => $attributes,
            'listIds' => [$this->requiredInteger('list_id')], 'updateEnabled' => true,
        ])->throw();
        $this->request()->post('/events', [
            'event_name' => $eventName,
            'identifiers' => ['email_id' => $lead->email],
            'event_properties' => array_filter([
                'event_key' => $eventKey, 'booking_status' => $booking->status->value,
                'starts_at_utc' => $booking->starts_at?->utc()->toIso8601String(),
                'timezone' => $booking->timezone,
            ]),
        ])->throw();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(config('services.brevo.base_url'))
            ->acceptJson()
            ->asJson()
            ->withHeader('api-key', $this->requiredString('api_key'))
            ->timeout(10);
    }

    private function requiredString(string $key): string
    {
        $value = config("services.brevo.{$key}");

        if (! is_string($value) || $value === '') {
            throw new RuntimeException("Brevo configuration [{$key}] is missing.");
        }

        return $value;
    }

    private function requiredInteger(string $key): int
    {
        $value = filter_var(config("services.brevo.{$key}"), FILTER_VALIDATE_INT);

        if ($value === false) {
            throw new RuntimeException("Brevo configuration [{$key}] must be an integer.");
        }

        return $value;
    }
}
