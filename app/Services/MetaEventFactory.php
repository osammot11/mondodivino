<?php

namespace App\Services;

use App\Models\HvcoLead;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MetaEventFactory
{
    public function make(
        string $eventName,
        string $eventId,
        ?Request $request = null,
        ?HvcoLead $lead = null,
        array $customData = [],
        ?string $sourceUrl = null,
        ?string $referrerUrl = null,
        ?int $eventTime = null,
    ): array {
        $userData = $this->userData($request, $lead);
        $event = [
            'event_name' => $eventName,
            'event_time' => $eventTime ?? now()->timestamp,
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $this->sanitizeUrl($sourceUrl ?? $request?->fullUrl()),
            'referrer_url' => $this->sanitizeUrl($referrerUrl ?? $request?->headers->get('referer')),
            'user_data' => $userData,
        ];

        if ($customData !== []) {
            $event['custom_data'] = array_filter($customData, fn (mixed $value): bool => $value !== null && $value !== '');
        }

        $options = config('services.meta.data_processing_options', []);
        if ($options !== []) {
            $event['data_processing_options'] = $options;
            $event['data_processing_options_country'] = config('services.meta.data_processing_options_country', 0);
            $event['data_processing_options_state'] = config('services.meta.data_processing_options_state', 0);
        }

        return array_filter($event, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function userData(?Request $request, ?HvcoLead $lead): array
    {
        $profile = $lead?->tasteProfile;
        $phone = $profile?->mobile_phone ?: $lead?->phone;
        $userData = array_filter([
            'em' => $this->hash($lead?->email, 'email'),
            'ph' => $this->hash($phone, 'phone'),
            'fn' => $this->hash($lead?->first_name, 'text'),
            'ln' => $this->hash($profile?->last_name, 'text'),
            'ct' => $this->hash($profile?->city, 'location'),
            'st' => $this->hash($profile?->state, 'location'),
            'zp' => $this->hash($lead?->zip, 'postal'),
            'country' => $lead ? hash('sha256', 'us') : null,
            'external_id' => $lead ? hash('sha256', "hvco:{$lead->id}") : null,
            'client_ip_address' => $request?->ip(),
            'client_user_agent' => $request?->userAgent(),
            'fbp' => $this->identifier($request?->input('fbp') ?: $request?->cookie('_fbp') ?: $lead?->meta_fbp),
            'fbc' => $this->identifier($request?->input('fbc') ?: $request?->cookie('_fbc') ?: $lead?->meta_fbc),
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        return $userData;
    }

    private function hash(?string $value, string $type): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = match ($type) {
            'email' => Str::lower(trim($value)),
            'phone' => $this->normalizePhone($value),
            'location' => preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii(trim($value)))),
            'postal' => preg_replace('/[^a-z0-9]/', '', Str::lower(trim($value))),
            default => Str::lower(trim($value)),
        };

        return $normalized === '' ? null : hash('sha256', $normalized);
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        return strlen($digits) === 10 ? '1'.$digits : $digits;
    }

    private function identifier(?string $value): ?string
    {
        if (! $value || strlen($value) > 255 || ! preg_match('/^fb\.[12]\.[A-Za-z0-9._:-]+$/', $value)) {
            return null;
        }

        return $value;
    }

    private function sanitizeUrl(?string $value): ?string
    {
        if (! $value || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($value);
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $path = preg_replace('#^/(taste-profile|private-session)/[^/]+#', '/$1', $path) ?? $path;
        parse_str($parts['query'] ?? '', $query);
        unset($query['signature'], $query['expires']);

        return ($parts['scheme'].'://'.$parts['host'])
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .$path
            .($query !== [] ? '?'.http_build_query($query) : '');
    }
}
