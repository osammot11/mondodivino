<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\FunnelStatus;
use App\Enums\TasteProfileStatus;
use App\Jobs\ProcessCalcomWebhook;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SyncBookingToBrevo;
use App\Models\Booking;
use App\Models\CalcomWebhookEvent;
use App\Models\HvcoLead;
use App\Models\TasteProfile;
use App\Services\CalcomWebhookProcessor;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'http://localhost:8000',
            'services.calcom.embed_url' => 'https://cal.com/luca/private-wine-session',
            'services.calcom.webhook_secret' => 'cal-secret',
            'services.calcom.support_email' => 'support@example.com',
        ]);
    }

    public function test_booking_page_uses_configured_embed_copy_and_opaque_metadata(): void
    {
        $lead = $this->lead();
        $response = $this->get(app(TasteProfileUrlGenerator::class)->booking($lead));

        $response->assertOk()
            ->assertSee('Claim Your Private Italian Wine &amp; Cellar Blueprint Session with Luca Gradi', false)
            ->assertSee('Both give Luca useful clues.')
            ->assertSee('Select your date and time')
            ->assertSee('support@example.com')
            ->assertSee('private-wine-session')
            ->assertSee('js/booking.js')
            ->assertDontSee('vite', false);
        $this->assertNotNull($lead->tasteProfile->fresh()->booking_reference);
    }

    public function test_webhook_rejects_missing_invalid_signature_and_invalid_json(): void
    {
        $body = json_encode($this->payload());
        $this->call('POST', route('webhooks.calcom'), [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertUnauthorized();
        $this->call('POST', route('webhooks.calcom'), [], [], [], $this->headers('wrong'), $body)->assertUnauthorized();
        $this->call('POST', route('webhooks.calcom'), [], [], [], $this->headers(hash_hmac('sha256', '{', 'cal-secret')), '{')->assertBadRequest();
        $this->assertDatabaseCount('calcom_webhook_events', 0);
    }

    public function test_authenticated_created_webhook_is_idempotent_and_only_worker_confirms_booking(): void
    {
        Queue::fake();
        $lead = $this->lead();
        $reference = $this->reference($lead);
        $body = json_encode($this->payload(metadata: ['funnel_reference' => $reference, 'videoCallUrl' => 'https://meet.example.test/session']));

        $this->signedWebhook($body)->assertStatus(202);
        $this->signedWebhook($body)->assertStatus(202);

        $this->assertSame(FunnelStatus::TasteProfileComplete, $lead->fresh()->funnel_status);
        $this->assertDatabaseCount('calcom_webhook_events', 1);
        Queue::assertPushed(ProcessCalcomWebhook::class, 1);

        (new ProcessCalcomWebhook(1))->handle(app(CalcomWebhookProcessor::class));

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::Booked, $booking->status);
        $this->assertSame(FunnelStatus::CallBooked, $lead->fresh()->funnel_status);
        $this->assertSame('https://meet.example.test/session', $booking->meeting_url);
        $this->assertDatabaseHas('funnel_events', ['name' => 'call_booked']);
        $this->assertDatabaseHas('calcom_webhook_events', ['result' => 'processed']);
        $this->assertNull(CalcomWebhookEvent::sole()->normalized_payload);
        Queue::assertPushed(SyncBookingToBrevo::class, fn ($job) => $job->eventName === 'call_booked');
    }

    public function test_reschedule_updates_same_booking_and_stale_cancel_is_ignored(): void
    {
        Queue::fake();
        $lead = $this->lead();
        $reference = $this->reference($lead);
        $this->process($this->payload(metadata: ['funnel_reference' => $reference]));
        $this->process($this->payload('BOOKING_RESCHEDULED', '2026-08-21T11:00:00Z', 'new-uid', [
            'rescheduleUid' => 'booking-uid', 'metadata' => ['funnel_reference' => $reference],
            'startTime' => '2026-09-02T17:00:00Z', 'endTime' => '2026-09-02T17:30:00Z',
        ]));

        $booking = Booking::sole();
        $this->assertSame('new-uid', $booking->provider_booking_uid);
        $this->assertSame('booking-uid', $booking->previous_provider_booking_uid);
        $this->assertSame(BookingStatus::Rescheduled, $booking->status);

        $this->process($this->payload('BOOKING_CANCELLED', '2026-08-20T09:00:00Z', 'new-uid'));
        $this->assertSame(BookingStatus::Rescheduled, $booking->fresh()->status);
        $this->assertDatabaseHas('calcom_webhook_events', ['event_type' => 'BOOKING_CANCELLED', 'result' => 'stale']);
    }

    public function test_confirmed_booking_queues_one_meta_schedule_event_and_exposes_its_deduplication_id(): void
    {
        Queue::fake();
        config(['services.meta.pixel_id' => '1234567890', 'services.meta.access_token' => 'token']);
        $lead = $this->lead();
        $lead->update(['meta_tracking_consented_at' => now()]);
        $reference = $this->reference($lead);

        $this->process($this->payload(metadata: ['funnel_reference' => $reference]));

        $booking = Booking::sole();
        Queue::assertPushed(SendMetaConversionEvent::class, fn (SendMetaConversionEvent $job): bool => $job->event['event_name'] === 'Schedule'
            && $job->event['event_id'] === "schedule_{$booking->id}");
        $this->get(app(TasteProfileUrlGenerator::class)->bookingStatus($lead))
            ->assertOk()
            ->assertJson(['confirmed' => true, 'meta_event_id' => "schedule_{$booking->id}"]);
        $this->assertDatabaseHas('funnel_events', ['name' => 'meta_schedule_queued']);
    }

    public function test_email_fallback_correlates_and_lifecycle_updates_local_source_of_truth(): void
    {
        Queue::fake();
        $lead = $this->lead();
        $this->process($this->payload());
        $this->process($this->payload('BOOKING_NO_SHOW_UPDATED', '2026-08-22T12:00:00Z', 'booking-uid', [
            'attendees' => [['email' => $lead->email, 'name' => 'Curious Explorer', 'timeZone' => 'America/New_York', 'noShow' => true]],
            'bookingUid' => 'booking-uid',
        ]));

        $this->assertSame(BookingStatus::NoShow, Booking::sole()->status);
        $this->assertSame(FunnelStatus::NoShow, $lead->fresh()->funnel_status);
        $this->assertDatabaseHas('funnel_events', ['name' => 'call_no_show']);
    }

    public function test_status_endpoint_is_signed_and_exposes_details_only_after_confirmation(): void
    {
        $lead = $this->lead();
        $url = app(TasteProfileUrlGenerator::class)->bookingStatus($lead);
        $this->get($url)->assertOk()->assertJson(['status' => 'pending_webhook', 'confirmed' => false])->assertJsonMissingPath('meeting_url');
        $this->get(parse_url($url, PHP_URL_PATH).'?signature=wrong')->assertNotFound();
    }

    public function test_status_waits_for_the_client_callback_uid_instead_of_returning_an_old_booking(): void
    {
        $lead = $this->lead();
        Booking::create([
            'hvco_lead_id' => $lead->id, 'taste_profile_id' => $lead->tasteProfile->id,
            'provider' => 'calcom', 'provider_booking_uid' => 'old-uid',
            'status' => BookingStatus::Booked, 'starts_at' => now()->addDay(),
            'provider_updated_at' => now(),
        ]);
        $url = app(TasteProfileUrlGenerator::class)->bookingStatus($lead);

        $this->withHeader('x-cal-booking-uid', 'new-uid')->getJson($url)
            ->assertOk()->assertJson(['status' => 'pending_webhook', 'confirmed' => false]);
        $this->withHeader('x-cal-booking-uid', 'old-uid')->getJson($url)
            ->assertOk()->assertJson(['status' => 'booked', 'confirmed' => true]);
    }

    private function process(array $payload): void
    {
        $body = json_encode($payload);
        $this->signedWebhook($body)->assertStatus(202);
        $event = CalcomWebhookEvent::latest('id')->firstOrFail();
        (new ProcessCalcomWebhook($event->id))->handle(app(CalcomWebhookProcessor::class));
    }

    private function signedWebhook(string $body)
    {
        return $this->call('POST', route('webhooks.calcom'), [], [], [], $this->headers(hash_hmac('sha256', $body, 'cal-secret')), $body);
    }

    private function headers(string $signature): array
    {
        return ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_CAL_SIGNATURE_256' => $signature];
    }

    private function payload(string $type = 'BOOKING_CREATED', string $createdAt = '2026-08-20T10:00:00Z', string $uid = 'booking-uid', array $overrides = [], array $metadata = []): array
    {
        return ['triggerEvent' => $type, 'createdAt' => $createdAt, 'payload' => array_merge([
            'uid' => $uid, 'eventTypeId' => 123, 'startTime' => '2026-09-01T16:00:00Z',
            'endTime' => '2026-09-01T16:30:00Z', 'metadata' => $metadata,
            'attendees' => [['email' => 'curious@example.com', 'name' => 'Curious Explorer', 'timeZone' => 'America/New_York']],
        ], $overrides)];
    }

    private function reference(HvcoLead $lead): string
    {
        $this->get(app(TasteProfileUrlGenerator::class)->booking($lead))->assertOk();

        return $lead->tasteProfile->fresh()->booking_reference;
    }

    private function lead(): HvcoLead
    {
        $lead = HvcoLead::create([
            'email' => 'curious@example.com', 'first_name' => 'Curious', 'landing_variant' => 'main',
            'consent_text' => 'Approved.', 'consent_at' => now(), 'locale' => 'en_US',
            'funnel_status' => FunnelStatus::TasteProfileComplete,
        ]);
        TasteProfile::create([
            'hvco_lead_id' => $lead->id, 'status' => TasteProfileStatus::Completed,
            'age_confirmed' => true, 'started_at' => now()->subMinute(), 'completed_at' => now(),
        ]);

        return $lead->fresh('tasteProfile');
    }
}
