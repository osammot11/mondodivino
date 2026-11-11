<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\FunnelStatus;
use App\Events\CallBooked;
use App\Events\CallCancelled;
use App\Events\CallCompleted;
use App\Events\CallNoShow;
use App\Events\CallRescheduled;
use App\Jobs\SyncBookingToBrevo;
use App\Models\Booking;
use App\Models\CalcomWebhookEvent;
use App\Models\FunnelEvent;
use App\Models\HvcoLead;
use App\Models\TasteProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalcomWebhookProcessor
{
    public function __construct(private readonly MetaTracking $metaTracking) {}

    public function process(int $eventId): void
    {
        $dispatch = null;
        $bookingId = null;
        $eventName = null;
        $trackSchedule = false;
        $scheduleEventTime = null;

        DB::transaction(function () use ($eventId, &$dispatch, &$bookingId, &$eventName, &$trackSchedule, &$scheduleEventTime): void {
            $event = CalcomWebhookEvent::whereKey($eventId)->lockForUpdate()->firstOrFail();
            if ($event->processed_at) {
                return;
            }

            $data = $event->normalized_payload;
            if (! is_array($data)) {
                $event->update(['processed_at' => now(), 'result' => 'invalid']);

                return;
            }

            $booking = $this->findBooking($data);
            $profile = $booking?->tasteProfile ?? $this->findProfile($data);
            if (! $profile) {
                $event->update(['processed_at' => now(), 'result' => 'unmatched', 'normalized_payload' => null]);

                return;
            }

            $lead = $profile->lead;
            $eventAt = isset($data['event_timestamp']) ? Carbon::parse($data['event_timestamp']) : now();
            $status = $this->status($data['event_type']);
            if ($this->isStale($booking, $status, $eventAt)) {
                $event->update(['processed_at' => now(), 'result' => 'stale', 'normalized_payload' => null]);

                return;
            }

            $booking ??= new Booking([
                'hvco_lead_id' => $lead->id,
                'taste_profile_id' => $profile->id,
                'provider' => 'calcom',
                'provider_booking_uid' => $data['booking_uid'],
            ]);

            if ($data['event_type'] === 'BOOKING_RESCHEDULED' && $booking->exists && $booking->provider_booking_uid !== $data['booking_uid']) {
                $booking->previous_provider_booking_uid = $booking->provider_booking_uid;
                $booking->provider_booking_uid = $data['booking_uid'];
            }

            $booking->fill(array_filter([
                'booking_reference' => $data['booking_reference'] ?? $profile->booking_reference,
                'event_type_id' => $data['event_type_id'], 'status' => $status,
                'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
                'timezone' => $data['timezone'], 'attendee_name' => $data['attendee_name'],
                'attendee_email' => $data['attendee_email'], 'meeting_url' => $data['meeting_url'],
                'reschedule_url' => $data['reschedule_url'], 'cancel_url' => $data['cancel_url'],
                'provider_updated_at' => $eventAt,
            ], fn ($value) => $value !== null));

            $timestampField = match ($status) {
                BookingStatus::Booked => 'booked_at', BookingStatus::Rescheduled => 'rescheduled_at',
                BookingStatus::Cancelled, BookingStatus::Rejected => 'cancelled_at',
                BookingStatus::Completed => 'completed_at', BookingStatus::NoShow => 'no_show_at',
                default => null,
            };
            if ($timestampField) {
                $booking->{$timestampField} = $eventAt;
            }
            if (! $booking->provider_created_at) {
                $booking->provider_created_at = $eventAt;
            }
            $booking->save();

            [$leadStatus, $eventName, $dispatch] = $this->effects($status);
            if (in_array($status, [BookingStatus::Cancelled, BookingStatus::Rejected], true)) {
                $hasActive = Booking::where('hvco_lead_id', $lead->id)->whereKeyNot($booking->id)
                    ->whereIn('status', [BookingStatus::Booked->value, BookingStatus::Rescheduled->value])->exists();
                $leadStatus = $hasActive ? FunnelStatus::CallBooked : FunnelStatus::TasteProfileComplete;
            }
            $lead->update(['funnel_status' => $leadStatus]);

            FunnelEvent::firstOrCreate(['event_key' => "calcom:{$event->payload_hash}"], [
                'hvco_lead_id' => $lead->id, 'name' => $eventName,
                'metadata' => ['booking_id' => $booking->id, 'provider' => 'calcom'],
                'occurred_at' => $eventAt,
            ]);
            $event->update(['processed_at' => now(), 'result' => 'processed', 'normalized_payload' => null]);
            $bookingId = $booking->id;

            if ($status === BookingStatus::Booked && $this->metaTracking->leadHasConsent($lead)) {
                $metaEvent = FunnelEvent::firstOrCreate(
                    ['event_key' => "meta:schedule:{$booking->id}"],
                    [
                        'hvco_lead_id' => $lead->id,
                        'name' => 'meta_schedule_queued',
                        'metadata' => ['booking_id' => $booking->id],
                        'occurred_at' => $eventAt,
                    ],
                );
                $trackSchedule = $metaEvent->wasRecentlyCreated;
                $scheduleEventTime = $eventAt->timestamp;
            }
        });

        if ($bookingId && $eventName) {
            $dispatch::dispatch($bookingId);
            SyncBookingToBrevo::dispatch($bookingId, $eventName, "calcom:{$eventId}");

            if ($trackSchedule) {
                $booking = Booking::with('lead.tasteProfile')->findOrFail($bookingId);
                $this->metaTracking->trackForLead(
                    $booking->lead,
                    'Schedule',
                    "schedule_{$booking->id}",
                    ['content_name' => 'Private Italian Wine Session'],
                    url('/private-session'),
                    $scheduleEventTime,
                );
            }
        }
    }

    private function findBooking(array $data): ?Booking
    {
        $uids = array_values(array_filter([$data['booking_uid'] ?? null, $data['previous_booking_uid'] ?? null]));

        return Booking::with('tasteProfile.lead')->where(function ($q) use ($uids): void {
            $q->whereIn('provider_booking_uid', $uids)->orWhereIn('previous_provider_booking_uid', $uids);
        })->lockForUpdate()->first();
    }

    private function findProfile(array $data): ?TasteProfile
    {
        if ($reference = ($data['booking_reference'] ?? null)) {
            $profile = TasteProfile::with('lead')->where('booking_reference', $reference)->first();
            if ($profile) {
                return $profile;
            }
        }
        if ($email = ($data['attendee_email'] ?? null)) {
            return HvcoLead::with('tasteProfile')->where('email', $email)->first()?->tasteProfile;
        }

        return null;
    }

    private function status(string $type): BookingStatus
    {
        return match ($type) {
            'BOOKING_CREATED', 'BOOKING_CONFIRMED' => BookingStatus::Booked,
            'BOOKING_RESCHEDULED' => BookingStatus::Rescheduled,
            'BOOKING_CANCELLED' => BookingStatus::Cancelled,
            'BOOKING_REJECTED' => BookingStatus::Rejected,
            'BOOKING_COMPLETED', 'MEETING_ENDED' => BookingStatus::Completed,
            default => BookingStatus::NoShow,
        };
    }

    private function isStale(?Booking $booking, BookingStatus $incoming, Carbon $eventAt): bool
    {
        if (! $booking || ! $booking->provider_updated_at) {
            return false;
        }
        if ($eventAt->lt($booking->provider_updated_at)) {
            return true;
        }

        $terminal = [BookingStatus::Cancelled, BookingStatus::Rejected, BookingStatus::Completed, BookingStatus::NoShow];

        return in_array($booking->status, $terminal, true) && $booking->status !== $incoming;
    }

    private function effects(BookingStatus $status): array
    {
        return match ($status) {
            BookingStatus::Booked => [FunnelStatus::CallBooked, 'call_booked', CallBooked::class],
            BookingStatus::Rescheduled => [FunnelStatus::CallBooked, 'call_rescheduled', CallRescheduled::class],
            BookingStatus::Cancelled, BookingStatus::Rejected => [FunnelStatus::TasteProfileComplete, 'call_cancelled', CallCancelled::class],
            BookingStatus::Completed => [FunnelStatus::CallCompleted, 'call_show', CallCompleted::class],
            BookingStatus::NoShow => [FunnelStatus::NoShow, 'call_no_show', CallNoShow::class],
            default => [FunnelStatus::TasteProfileComplete, 'call_cancelled', CallCancelled::class],
        };
    }
}
