<?php

namespace App\Services;

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
