<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\FunnelEvent;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function show(Request $request, string $token, TasteProfileUrlGenerator $urls): View|Response
    {
        $lead = $urls->resolve($request, $token);

        if (! $lead || ! $lead->tasteProfile?->completed_at || ! $lead->tasteProfile->age_confirmed) {
            return response()->view('hvco.profile-link-invalid', [], 410);
        }

        if (! $lead->tasteProfile->booking_reference) {
            $lead->tasteProfile->update(['booking_reference' => Str::random(48)]);
        }

        FunnelEvent::create([
            'hvco_lead_id' => $lead->id,
            'name' => 'booking_page_view',
            'occurred_at' => now(),
        ]);

        $embed = $this->embedConfiguration();
        $booking = $lead->bookings()->latest('provider_updated_at')->first();
        $isConfirmed = $booking && in_array($booking->status, [BookingStatus::Booked, BookingStatus::Rescheduled], true);

        return view('hvco.booking', [
            'lead' => $lead->fresh('tasteProfile'),
            'booking' => $booking,
            'bookingStatusUrl' => $urls->bookingStatus($lead),
            'calConfig' => $embed,
            'supportEmail' => config('services.calcom.support_email'),
            'bookingDuration' => config('services.calcom.booking_duration'),
            'metaConsentUrl' => $urls->metaConsent($lead),
            'metaBrowserEvents' => $isConfirmed ? [[
                'name' => 'Schedule',
                'event_id' => "schedule_{$booking->id}",
                'parameters' => ['content_name' => 'Private Italian Wine Session'],
            ]] : [],
        ]);
    }

    public function status(Request $request, string $token, TasteProfileUrlGenerator $urls): JsonResponse
    {
        $lead = $urls->resolve($request, $token);
        abort_unless($lead && $lead->tasteProfile?->completed_at && $lead->tasteProfile->age_confirmed, 404);

        $booking = $lead->bookings()->latest('provider_updated_at')->first();
        if (! $booking) {
            return response()->json(['status' => BookingStatus::PendingWebhook->value, 'confirmed' => false]);
        }

        $expectedUid = trim((string) $request->header('x-cal-booking-uid'));
        if ($expectedUid !== '' && ! hash_equals($booking->provider_booking_uid, mb_substr($expectedUid, 0, 255))) {
            return response()->json(['status' => BookingStatus::PendingWebhook->value, 'confirmed' => false]);
        }

        $confirmed = in_array($booking->status, [BookingStatus::Booked, BookingStatus::Rescheduled], true);

        return response()->json(array_filter([
            'status' => $booking->status->value,
            'confirmed' => $confirmed,
            'starts_at' => $confirmed ? $booking->starts_at?->utc()->toIso8601String() : null,
            'ends_at' => $confirmed ? $booking->ends_at?->utc()->toIso8601String() : null,
            'timezone' => $confirmed ? $booking->timezone : null,
            'meeting_url' => $confirmed ? $booking->meeting_url : null,
            'reschedule_url' => $confirmed ? $booking->reschedule_url : null,
            'cancel_url' => $confirmed ? $booking->cancel_url : null,
            'meta_event_id' => $confirmed ? "schedule_{$booking->id}" : null,
        ], fn ($value) => $value !== null));
    }

    private function embedConfiguration(): ?array
    {
        $url = trim((string) config('services.calcom.embed_url'));
        if ($url === '') {
            $slug = trim((string) config('services.calcom.event_type_slug'));
            $team = trim((string) config('services.calcom.team_slug'));
            $username = trim((string) config('services.calcom.username'));
            if ($team !== '' && $slug !== '') {
                $url = 'https://cal.com/team/'.rawurlencode($team).'/'.rawurlencode($slug);
            } elseif ($username !== '' && $slug !== '') {
                $url = 'https://cal.com/'.rawurlencode($username).'/'.rawurlencode($slug);
            }
        }
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        return [
            'origin' => rtrim(parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).(parse_url($url, PHP_URL_PORT) ? ':'.parse_url($url, PHP_URL_PORT) : ''), '/'),
            'calLink' => trim((string) parse_url($url, PHP_URL_PATH), '/'),
            'eventTypeId' => config('services.calcom.event_type_id'),
        ];
    }
}
