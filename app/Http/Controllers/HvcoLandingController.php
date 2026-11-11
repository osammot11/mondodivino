<?php

namespace App\Http\Controllers;

use App\Enums\FunnelStatus;
use App\Http\Requests\StoreHvcoLeadRequest;
use App\Jobs\SendHvcoGuideEmail;
use App\Jobs\SyncHvcoLeadToBrevo;
use App\Models\FunnelEvent;
use App\Models\HvcoLead;
use App\Services\MetaTracking;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HvcoLandingController extends Controller
{
    private const UTM_FIELDS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public function show(Request $request): View
    {
        FunnelEvent::create([
            'name' => 'landing_view',
            'landing_variant' => config('funnel.landing_variant'),
            'metadata' => Arr::whereNotNull(Arr::only($request->query(), self::UTM_FIELDS)),
            'occurred_at' => now(),
        ]);

        return view('hvco.landing', [
            'variant' => config('funnel.landing_variant'),
            'collectZip' => config('funnel.collect_zip'),
            'consentText' => config('funnel.consent_text'),
            'guideMockup' => config('funnel.guide_mockup'),
            'tracking' => Arr::whereNotNull(Arr::only($request->query(), self::UTM_FIELDS)),
            'referrer' => $request->headers->get('referer'),
        ]);
    }

    public function store(
        StoreHvcoLeadRequest $request,
        TasteProfileUrlGenerator $urls,
        MetaTracking $metaTracking,
    ): RedirectResponse {
        $data = $request->validated();

        $lead = DB::transaction(function () use ($data, $request): HvcoLead {
            $existingLead = HvcoLead::where('email', $data['email'])->first();
            $lead = HvcoLead::updateOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'phone' => $data['phone'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    ...Arr::only($data, self::UTM_FIELDS),
                    'referrer' => $data['referrer'] ?? null,
                    'landing_variant' => config('funnel.landing_variant'),
                    'consent_text' => config('funnel.consent_text'),
                    'consent_at' => now(),
                    'locale' => $request->getPreferredLanguage() ?: config('app.locale'),
                    'funnel_status' => $existingLead?->funnel_status ?? FunnelStatus::HvcoLead,
                ]
            );

            FunnelEvent::create([
                'hvco_lead_id' => $lead->id,
                'name' => 'hvco_optin',
                'landing_variant' => $lead->landing_variant,
                'metadata' => Arr::whereNotNull(Arr::only($data, self::UTM_FIELDS)),
                'occurred_at' => now(),
            ]);

            return $lead;
        });

        SyncHvcoLeadToBrevo::dispatch($lead->id)->afterCommit();
        SendHvcoGuideEmail::dispatch($lead->id)->afterCommit();

        if ($metaTracking->hasConsent($request)) {
            $eventId = (string) Str::uuid();
            $metaTracking->trackFromRequest($request, 'Lead', $eventId, $lead, [
                'content_name' => 'HVCO Guide Opt-in',
                'content_category' => 'Lead generation',
            ]);
            $request->session()->flash('meta_browser_events', [[
                'name' => 'Lead',
                'event_id' => $eventId,
                'parameters' => ['content_name' => 'HVCO Guide Opt-in'],
            ]]);
        }

        return redirect()->to($urls->profile($lead));
    }
}
