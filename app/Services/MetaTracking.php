<?php

namespace App\Services;

use App\Jobs\SendMetaConversionEvent;
use App\Models\HvcoLead;
use Illuminate\Http\Request;

class MetaTracking
{
    public function __construct(private readonly MetaEventFactory $events) {}

    public function pixelConfigured(): bool
    {
        return trim((string) config('services.meta.pixel_id')) !== '';
    }

    public function hasConsent(Request $request): bool
    {
        return $request->cookie($this->consentCookie()) === 'granted';
    }

    public function leadHasConsent(HvcoLead $lead): bool
    {
        return $lead->meta_tracking_consented_at !== null && $lead->meta_tracking_revoked_at === null;
    }

    public function rememberConsent(HvcoLead $lead, Request $request): void
    {
        if (! $this->hasConsent($request)) {
            return;
        }

        $lead->update([
            'meta_fbp' => $this->identifier($request->cookie('_fbp')) ?: $lead->meta_fbp,
            'meta_fbc' => $this->identifier($request->cookie('_fbc')) ?: $lead->meta_fbc,
            'meta_tracking_consented_at' => $lead->meta_tracking_consented_at ?? now(),
            'meta_tracking_revoked_at' => null,
        ]);
    }

    public function trackFromRequest(
        Request $request,
        string $eventName,
        string $eventId,
        ?HvcoLead $lead = null,
        array $customData = [],
        ?string $sourceUrl = null,
        ?string $referrerUrl = null,
    ): void {
        if (! $this->hasConsent($request)) {
            return;
        }

        if ($lead) {
            $this->rememberConsent($lead, $request);
            $lead->refresh();
        }

        SendMetaConversionEvent::dispatch($this->events->make(
            $eventName,
            $eventId,
            $request,
            $lead?->loadMissing('tasteProfile'),
            $customData,
            $sourceUrl,
            $referrerUrl,
        ))->afterCommit();
    }

    public function trackForLead(
        HvcoLead $lead,
        string $eventName,
        string $eventId,
        array $customData = [],
        ?string $sourceUrl = null,
        ?int $eventTime = null,
    ): void {
        if (! $this->leadHasConsent($lead)) {
            return;
        }

        SendMetaConversionEvent::dispatch($this->events->make(
            $eventName,
            $eventId,
            lead: $lead->loadMissing('tasteProfile'),
            customData: $customData,
            sourceUrl: $sourceUrl,
            referrerUrl: null,
            eventTime: $eventTime,
        ))->afterCommit();
    }

    public function consentCookie(): string
    {
        return (string) config('services.meta.consent_cookie', 'mdv_meta_consent');
    }

    public function identifier(?string $value): ?string
    {
        if (! $value || strlen($value) > 255 || ! preg_match('/^fb\.[12]\.[A-Za-z0-9._:-]+$/', $value)) {
            return null;
        }

        return $value;
    }
}
