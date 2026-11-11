<?php

namespace App\Http\Controllers;

use App\Enums\FunnelStatus;
use App\Enums\TasteProfileStatus;
use App\Events\TasteProfileCompleted;
use App\Http\Requests\StoreTasteProfileRequest;
use App\Jobs\SyncTasteProfileToBrevo;
use App\Models\FunnelEvent;
use App\Models\TasteProfile;
use App\Services\MetaTracking;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TasteProfileController extends Controller
{
    public function show(Request $request, string $token, TasteProfileUrlGenerator $urls): View|Response
    {
        $lead = $urls->resolve($request, $token);

        if (! $lead) {
            return $this->invalidLink();
        }

        $profile = TasteProfile::firstOrCreate(
            ['hvco_lead_id' => $lead->id],
            ['status' => TasteProfileStatus::Draft, 'started_at' => now()],
        );

        FunnelEvent::firstOrCreate(
            ['event_key' => "taste_form_started:{$profile->id}"],
            ['hvco_lead_id' => $lead->id, 'name' => 'taste_form_started', 'occurred_at' => now()],
        );
        FunnelEvent::create([
            'hvco_lead_id' => $lead->id,
            'name' => 'taste_page_view',
            'occurred_at' => now(),
        ]);

        return view('hvco.taste-profile', [
            'lead' => $lead,
            'profile' => $profile,
            'states' => config('funnel.states'),
            'profileConsentText' => config('funnel.profile_consent_text'),
            'smsConsentText' => config('funnel.sms_consent_text'),
            'metaConsentUrl' => $urls->metaConsent($lead),
        ]);
    }

    public function store(
        StoreTasteProfileRequest $request,
        string $token,
        TasteProfileUrlGenerator $urls,
        MetaTracking $metaTracking,
    ): RedirectResponse|View|Response {
        $lead = $urls->resolve($request, $token);

        if (! $lead) {
            return $this->invalidLink();
        }

        $data = $request->validated();
        $profile = TasteProfile::firstOrCreate(
            ['hvco_lead_id' => $lead->id],
            ['status' => TasteProfileStatus::Draft, 'started_at' => now()],
        );

        if (! $data['age_confirmed']) {
            $profile->update(['age_confirmed' => false]);

            return redirect()->to($urls->ineligible($lead));
        }

        $completionWasCreated = false;

        DB::transaction(function () use ($data, $lead, $profile, &$completionWasCreated): void {
            $profile = TasteProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $serviceableStates = config('funnel.serviceable_states');
            $minimumBudget = config('funnel.minimum_set_budget');
            $budgets = config('funnel.profile_options.set_budgets');
            $budgetPosition = array_search($data['set_budget'], $budgets, true);
            $minimumPosition = $minimumBudget ? array_search($minimumBudget, $budgets, true) : false;

            $lead->update([
                'first_name' => $data['first_name'],
                'zip' => $data['zip'],
                'funnel_status' => FunnelStatus::TasteProfileComplete,
            ]);

            $profile->update([
                ...Arr::only($data, [
                    'last_name', 'mobile_phone', 'city', 'state', 'age_confirmed',
                    'wine_styles', 'taste_descriptions', 'favorite_wines', 'avoidances',
                    'avoidance_other', 'adventure_level', 'occasions', 'preferred_mix',
                    'usual_bottle_spend', 'set_budget', 'desired_timing', 'italian_memory',
                    'additional_notes', 'sms_consent',
                ]),
                'status' => TasteProfileStatus::Completed,
                'profile_consent_text' => config('funnel.profile_consent_text'),
                'profile_consented_at' => now(),
                'sms_consented_at' => $data['sms_consent'] ? ($profile->sms_consented_at ?? now()) : null,
                'is_serviceable' => $serviceableStates === [] ? null : in_array($data['state'], $serviceableStates, true),
                'is_budget_qualified' => $minimumPosition === false ? null : $budgetPosition >= $minimumPosition,
                'completed_at' => $profile->completed_at ?? now(),
            ]);

            $event = FunnelEvent::firstOrCreate(
                ['event_key' => "taste_profile_completed:{$profile->id}"],
                [
                    'hvco_lead_id' => $lead->id,
                    'name' => 'taste_profile_completed',
                    'occurred_at' => now(),
                ],
            );
            $completionWasCreated = $event->wasRecentlyCreated;
        });

        $profile->refresh();

        if ($completionWasCreated) {
            TasteProfileCompleted::dispatch($profile->id);
        }

        SyncTasteProfileToBrevo::dispatch($profile->id, $completionWasCreated)->afterCommit();

        if ($completionWasCreated && $metaTracking->hasConsent($request)) {
            $eventId = (string) Str::uuid();
            $metaTracking->trackFromRequest($request, 'CompleteRegistration', $eventId, $lead, [
                'content_name' => 'HVCO Taste Profile',
                'status' => 'completed',
            ]);
            $request->session()->flash('meta_browser_events', [[
                'name' => 'CompleteRegistration',
                'event_id' => $eventId,
                'parameters' => ['content_name' => 'HVCO Taste Profile'],
            ]]);
        }

        if ($profile->is_serviceable === false) {
            return redirect()->to($urls->unavailable($lead));
        }

        return redirect()->to($urls->booking($lead));
    }

    public function ineligible(Request $request, string $token, TasteProfileUrlGenerator $urls): View|Response
    {
        $lead = $urls->resolve($request, $token);

        return $lead
            ? view('hvco.profile-ineligible', ['metaConsentUrl' => $urls->metaConsent($lead)])
            : $this->invalidLink();
    }

    public function unavailable(Request $request, string $token, TasteProfileUrlGenerator $urls): View|Response
    {
        $lead = $urls->resolve($request, $token);

        return $lead
            ? view('hvco.profile-unavailable', ['metaConsentUrl' => $urls->metaConsent($lead)])
            : $this->invalidLink();
    }

    private function invalidLink(): Response
    {
        return response()->view('hvco.profile-link-invalid', [], 410);
    }
}
