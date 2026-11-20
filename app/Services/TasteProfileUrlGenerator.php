<?php

namespace App\Services;

use App\Enums\TasteProfileStatus;
use App\Models\HvcoLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TasteProfileUrlGenerator
{
    public function profile(HvcoLead $lead): string
    {
        return $this->signedRoute('taste-profile.show', $lead);
    }

    public function booking(HvcoLead $lead): string
    {
        return $this->signedRoute('booking.show', $lead);
    }

    public function bookingStatus(HvcoLead $lead): string
    {
        return $this->signedRoute('booking.status', $lead);
    }

    public function metaConsent(HvcoLead $lead): string
    {
        return $this->signedRoute('tracking.meta.consent', $lead);
    }

    public function ineligible(HvcoLead $lead): string
    {
        return $this->signedRoute('taste-profile.ineligible', $lead);
    }

    public function unavailable(HvcoLead $lead): string
    {
        return $this->signedRoute('taste-profile.unavailable', $lead);
    }

    /**
     * The page a returning lead should land on: the taste profile until it is
     * completed, then the booking page, or the neutral page for leads who are
     * under age or outside the serviceable states.
     */
    public function nextStep(HvcoLead $lead): string
    {
        $profile = $lead->tasteProfile;

        if ($profile?->age_confirmed === false) {
            return $this->ineligible($lead);
        }

        if ($profile?->status !== TasteProfileStatus::Completed) {
            return $this->profile($lead);
        }

        return $profile->is_serviceable === false
            ? $this->unavailable($lead)
            : $this->booking($lead);
    }

    public function resolve(Request $request, string $token): ?HvcoLead
    {
        if (! $request->hasValidSignature()) {
            return null;
        }

        return HvcoLead::where('taste_profile_token', $token)->first();
    }

    private function signedRoute(string $route, HvcoLead $lead): string
    {
        if (! $lead->taste_profile_token) {
            $lead->update(['taste_profile_token' => Str::random(48)]);
        }

        return URL::temporarySignedRoute(
            $route,
            now()->addDays((int) config('funnel.taste_profile_link_days')),
            ['token' => $lead->taste_profile_token],
        );
    }
}
