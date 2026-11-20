<?php

namespace App\Http\Controllers;

use App\Jobs\SendSessionLinkEmail;
use App\Models\FunnelEvent;
use App\Models\HvcoLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Generic entry point for links that cannot be personal, such as the button
 * inside the HVCO PDF. The visitor enters their email and, if it belongs to a
 * lead, the signed personal link is emailed to them. The response is identical
 * whether or not the email is known, so the page never reveals who is a lead.
 */
class SessionLinkController extends Controller
{
    private const COOLDOWN_SECONDS = 600;

    public function show(): View
    {
        return view('hvco.session-link');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'website' => ['nullable', 'max:0'],
        ]);

        $lead = HvcoLead::where('email', mb_strtolower(trim($data['email'])))->first();

        // Leads who declared they are under 21 never receive commercial session links.
        $isEligible = $lead && $lead->tasteProfile?->age_confirmed !== false;

        if ($isEligible && Cache::add("session-link:{$lead->id}", true, self::COOLDOWN_SECONDS)) {
            FunnelEvent::create([
                'hvco_lead_id' => $lead->id,
                'name' => 'session_link_requested',
                'occurred_at' => now(),
            ]);
            SendSessionLinkEmail::dispatch($lead->id);
        }

        return redirect()->route('session-link.show')->with('session_link_sent', true);
    }
}
