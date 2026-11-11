<?php

namespace Tests\Feature;

use App\Enums\FunnelStatus;
use App\Jobs\SendHvcoGuideEmail;
use App\Jobs\SyncHvcoLeadToBrevo;
use App\Models\HvcoLead;
use App\Services\BrevoClient;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HvcoFunnelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'funnel.collect_zip' => false,
            'funnel.landing_variant' => 'main',
            'funnel.consent_text' => 'Approved consent.',
            'services.brevo.api_key' => 'test-key',
            'services.brevo.list_id' => 42,
            'services.brevo.hvco_template_id' => 77,
            'services.brevo.hvco_guide_url' => 'https://example.test/guide.pdf',
            'services.brevo.webhook_secret' => 'webhook-test-secret',
            'services.brevo.base_url' => 'https://api.brevo.test/v3',
        ]);
    }

    public function test_landing_renders_approved_copy_and_tracks_view(): void
    {
        $this->get(route('hvco.landing', ['utm_source' => 'meta']))
            ->assertOk()
            ->assertSee('Discover the Italian Wines America Usually Misses')
            ->assertSee('THE PRODUCER-FIRST SELECTION METHOD')
            ->assertSee('SEND ME THE FREE GUIDE')
            ->assertSee('Mobile phone')
            ->assertDontSee('ZIP code')
            ->assertDontSee('PLACEHOLDER', false)
            ->assertDontSee('certified', false)
            ->assertDontSee('Exceptional Value', false);

        $this->assertDatabaseHas('funnel_events', ['name' => 'landing_view', 'landing_variant' => 'main']);
    }

    public function test_valid_submit_normalizes_and_persists_lead_dispatches_jobs_and_redirects(): void
    {
        Queue::fake();
        $before = now()->subSecond();

        $this->from('/italy-beyond-famous-labels?utm_source=meta')
            ->post(route('hvco.store'), [
                'first_name' => '  Curious  ',
                'email' => '  CURIOUS@Example.COM ',
                'phone' => '+1 555-123-4567',
                'consent' => '1',
                'website' => '',
                'landing_variant' => 'main',
                'referrer' => 'https://referrer.test/source',
                'utm_source' => 'meta',
            ])
            ->assertRedirect();

        $lead = HvcoLead::sole();
        $this->assertSame('Curious', $lead->first_name);
        $this->assertSame('curious@example.com', $lead->email);
        $this->assertSame('+1 555-123-4567', $lead->phone);
        $this->assertSame('meta', $lead->utm_source);
        $this->assertSame('main', $lead->landing_variant);
        $this->assertSame(FunnelStatus::HvcoLead, $lead->funnel_status);
        $this->assertNotNull($lead->taste_profile_token);
        $this->assertTrue($lead->consent_at->between($before, now()->addSecond()));

        $this->assertDatabaseHas('funnel_events', [
            'hvco_lead_id' => $lead->id,
            'name' => 'hvco_optin',
            'landing_variant' => 'main',
        ]);

        Queue::assertPushed(SyncHvcoLeadToBrevo::class, fn ($job) => $job->leadId === $lead->id);
        Queue::assertPushed(SendHvcoGuideEmail::class, fn ($job) => $job->leadId === $lead->id);
    }

    public function test_submit_flow_lands_on_real_taste_profile_page(): void
    {
        Queue::fake();

        $response = $this->post(route('hvco.store'), $this->payload());

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Your guide is on its way.')
            ->assertSee('Map Your Personal Italian Wine Profile', false);
    }

    public function test_duplicate_submit_updates_instead_of_creating_a_second_lead(): void
    {
        Queue::fake();
        $this->post(route('hvco.store'), $this->payload(['first_name' => 'Old']));
        $this->post(route('hvco.store'), $this->payload(['first_name' => 'New', 'email' => 'CURIOUS@EXAMPLE.COM']));

        $this->assertDatabaseCount('hvco_leads', 1);
        $this->assertDatabaseHas('hvco_leads', ['email' => 'curious@example.com', 'first_name' => 'New']);
    }

    public function test_reoptin_does_not_downgrade_a_completed_lead(): void
    {
        Queue::fake();
        $lead = $this->lead();
        $lead->update(['funnel_status' => FunnelStatus::TasteProfileComplete]);

        $this->post(route('hvco.store'), $this->payload(['first_name' => 'Updated']))->assertRedirectContains('/taste-profile/');

        $this->assertSame(FunnelStatus::TasteProfileComplete, $lead->fresh()->funnel_status);
        $this->assertSame('Updated', $lead->fresh()->first_name);
        $this->assertDatabaseCount('hvco_leads', 1);
    }

    public function test_validation_rejects_missing_fields_bad_email_and_honeypot(): void
    {
        Queue::fake();
        $this->post(route('hvco.store'), [
            'first_name' => '',
            'email' => 'not-an-email',
            'phone' => '',
            'website' => 'bot.example',
        ])->assertSessionHasErrors(['first_name', 'email', 'phone', 'consent', 'website']);

        $this->assertDatabaseCount('hvco_leads', 0);
        Queue::assertNothingPushed();
    }

    public function test_zip_can_be_enabled_for_the_configured_variant(): void
    {
        Queue::fake();
        config(['funnel.collect_zip' => true]);

        $this->get(route('hvco.landing'))->assertSee('ZIP code');
        $this->post(route('hvco.store'), $this->payload(['zip' => '10001']))->assertRedirect();
        $this->assertDatabaseHas('hvco_leads', ['zip' => '10001']);
    }

    public function test_optin_redirect_renders_long_page_with_secure_link(): void
    {
        Queue::fake();
        $response = $this->post(route('hvco.store'), $this->payload());
        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertStringNotContainsString('curious@example.com', $location);
        $this->assertStringNotContainsString('/1?', $location);
        $this->get($location)
            ->assertOk()
            ->assertSee('Your guide is on its way.')
            ->assertSee('Map Your Personal Italian Wine Profile', false);
    }

    public function test_brevo_sync_job_upserts_contact_and_tracks_optin(): void
    {
        Http::fake([
            'api.brevo.test/v3/contacts' => Http::response([], 201),
            'api.brevo.test/v3/events' => Http::response([], 204),
        ]);
        $lead = $this->lead();

        (new SyncHvcoLeadToBrevo($lead->id))->handle(app(BrevoClient::class));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.test/v3/contacts'
            && $request['updateEnabled'] === true
            && $request['listIds'] === [42]
            && $request['attributes']['FUNNEL_STATUS'] === 'HVCO_LEAD'
            && $request->hasHeader('api-key', 'test-key'));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.test/v3/events'
            && $request['event_name'] === 'hvco_optin');
        $this->assertNotNull($lead->fresh()->brevo_synced_at);
    }

    public function test_brevo_sync_error_is_rethrown_for_queue_retry(): void
    {
        Http::fake(['*' => Http::response(['message' => 'unavailable'], 503)]);
        $job = new SyncHvcoLeadToBrevo($this->lead()->id);

        $this->assertSame(5, $job->tries);
        $this->assertSame([30, 120, 600, 1800], $job->backoff());
        $this->expectException(RequestException::class);
        $job->handle(app(BrevoClient::class));
    }

    public function test_guide_email_job_uses_transactional_template_and_configured_url(): void
    {
        Http::fake(['api.brevo.test/v3/smtp/email' => Http::response(['messageId' => 'm-1'], 201)]);
        $lead = $this->lead();

        (new SendHvcoGuideEmail($lead->id))->handle(app(BrevoClient::class), app(TasteProfileUrlGenerator::class));

        Http::assertSent(fn (Request $request) => $request['templateId'] === 77
            && $request['params']['guide_url'] === 'https://example.test/guide.pdf'
            && str_contains($request['params']['profile_url'], '/taste-profile/')
            && $request['to'][0]['email'] === 'curious@example.com');
        $this->assertNotNull($lead->fresh()->guide_email_requested_at);
    }

    public function test_valid_webhook_records_delivery_idempotently(): void
    {
        $lead = $this->lead();
        $payload = ['event' => 'delivered', 'email' => $lead->email, 'message-id' => 'm-1', 'ts_event' => 1_787_086_800];

        $this->withToken('webhook-test-secret')->postJson(route('webhooks.brevo'), $payload)->assertOk();
        $this->withToken('webhook-test-secret')->postJson(route('webhooks.brevo'), $payload)->assertOk();

        $this->assertDatabaseCount('brevo_webhook_events', 1);
        $this->assertDatabaseHas('funnel_events', ['hvco_lead_id' => $lead->id, 'name' => 'guide_email_delivered']);
        $this->assertSame('delivered', $lead->fresh()->email_status);
    }

    public function test_webhook_rejects_missing_or_wrong_secret(): void
    {
        $payload = ['event' => 'delivered', 'email' => 'curious@example.com'];
        $this->postJson(route('webhooks.brevo'), $payload)->assertUnauthorized();
        $this->withToken('wrong')->postJson(route('webhooks.brevo'), $payload)->assertUnauthorized();
        $this->assertDatabaseCount('brevo_webhook_events', 0);
    }

    public function test_bounce_and_unsubscribe_suppress_the_lead(): void
    {
        foreach (['hardBounce', 'invalid', 'unsubscribed'] as $event) {
            $lead = $this->lead("{$event}@example.com");
            $this->withToken('webhook-test-secret')->postJson(route('webhooks.brevo'), [
                'event' => $event,
                'email' => $lead->email,
                'message-id' => "m-{$event}",
            ])->assertOk();

            $this->assertNotNull($lead->fresh()->suppressed_at);
        }
    }

    public function test_optin_is_rate_limited(): void
    {
        Queue::fake();

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('hvco.store'), $this->payload(['email' => "lead{$i}@example.com"]))->assertRedirect();
        }

        $this->post(route('hvco.store'), $this->payload(['email' => 'limited@example.com']))->assertTooManyRequests();
        $this->assertDatabaseCount('hvco_leads', 10);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Curious',
            'email' => 'curious@example.com',
            'phone' => '+1 555-123-4567',
            'consent' => '1',
            'landing_variant' => 'main',
        ], $overrides);
    }

    private function lead(string $email = 'curious@example.com'): HvcoLead
    {
        return HvcoLead::create([
            'email' => $email,
            'first_name' => 'Curious',
            'landing_variant' => 'main',
            'consent_text' => 'Approved consent.',
            'consent_at' => now(),
            'locale' => 'en_US',
            'funnel_status' => FunnelStatus::HvcoLead,
        ]);
    }
}
