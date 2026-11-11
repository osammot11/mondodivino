<?php

namespace Tests\Feature;

use App\Enums\FunnelStatus;
use App\Jobs\SendMetaConversionEvent;
use App\Models\HvcoLead;
use App\Services\MetaConversionsApi;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'app.url' => 'http://localhost',
            'services.meta.pixel_id' => '1234567890',
            'services.meta.access_token' => 'meta-secret-token',
            'services.meta.graph_api_version' => 'v26.0',
            'services.meta.graph_base_url' => 'https://graph.meta.test',
            'services.meta.test_event_code' => 'TEST123',
            'services.meta.privacy_policy_url' => 'https://example.com/privacy',
        ]);
    }

    public function test_shared_layout_exposes_consent_gated_pixel_on_funnel_pages(): void
    {
        $this->get(route('hvco.landing'))
            ->assertOk()
            ->assertSee('js/meta-tracking.js')
            ->assertSee('1234567890')
            ->assertSee('Allow Meta measurement')
            ->assertSee('Privacy choices')
            ->assertSee('https://example.com/privacy');
    }

    public function test_page_view_capi_is_queued_only_after_tracking_consent(): void
    {
        Queue::fake();
        $payload = [
            'event_id' => 'pageview_123',
            'fbp' => 'fb.1.1787000000000.123456789',
            'event_source_url' => 'http://localhost:8000/taste-profile/private-token?expires=123&signature=secret',
            'referrer_url' => 'https://www.facebook.com/',
        ];

        $this->postJson(route('tracking.meta.page-view'), $payload)->assertAccepted();
        Queue::assertNotPushed(SendMetaConversionEvent::class);

        $this->withCredentials()
            ->withUnencryptedCookie('mdv_meta_consent', 'granted')
            ->withHeader('User-Agent', 'Tracking Test Browser')
            ->postJson(route('tracking.meta.page-view'), $payload)
            ->assertAccepted();

        Queue::assertPushed(SendMetaConversionEvent::class, function (SendMetaConversionEvent $job): bool {
            return $job->event['event_name'] === 'PageView'
                && $job->event['event_id'] === 'pageview_123'
                && $job->event['event_source_url'] === 'http://localhost:8000/taste-profile'
                && $job->event['referrer_url'] === 'https://www.facebook.com/'
                && $job->event['user_data']['fbp'] === 'fb.1.1787000000000.123456789'
                && $job->event['user_data']['client_user_agent'] === 'Tracking Test Browser';
        });
    }

    public function test_successful_optin_queues_deduplicated_lead_and_hashes_contact_data(): void
    {
        Queue::fake();
        $response = $this->withUnencryptedCookies([
            'mdv_meta_consent' => 'granted',
            '_fbp' => 'fb.1.1787000000000.123456789',
            '_fbc' => 'fb.1.1787000000000.click-id',
        ])->post(route('hvco.store'), [
            'first_name' => 'Curious',
            'email' => 'CURIOUS@example.com',
            'phone' => '+1 212 555 0100',
            'consent' => '1',
            'landing_variant' => 'main',
        ]);

        $lead = HvcoLead::sole();
        $this->assertNotNull($lead->meta_tracking_consented_at);
        $this->assertSame('fb.1.1787000000000.click-id', $lead->meta_fbc);

        Queue::assertPushed(SendMetaConversionEvent::class, function (SendMetaConversionEvent $job): bool {
            return $job->event['event_name'] === 'Lead'
                && $job->event['user_data']['em'] === hash('sha256', 'curious@example.com')
                && ! str_contains(json_encode($job->event), 'curious@example.com');
        });

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Lead', false);
    }

    public function test_capi_uses_v26_bearer_auth_and_optional_test_event_code(): void
    {
        Http::fake(['graph.meta.test/*' => Http::response(['events_received' => 1], 200)]);
        $event = [
            'event_name' => 'Lead',
            'event_time' => now()->timestamp,
            'event_id' => 'lead_1',
            'action_source' => 'website',
            'user_data' => ['em' => hash('sha256', 'curious@example.com')],
        ];

        (new SendMetaConversionEvent($event))->handle(app(MetaConversionsApi::class));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://graph.meta.test/v26.0/1234567890/events'
            && $request->hasHeader('Authorization', 'Bearer meta-secret-token')
            && $request['test_event_code'] === 'TEST123'
            && $request['data'][0]['event_id'] === 'lead_1');
    }

    public function test_signed_consent_endpoint_persists_revocation_and_clears_meta_ids(): void
    {
        $lead = $this->lead();
        $lead->update([
            'meta_fbp' => 'fb.1.1787000000000.123456789',
            'meta_fbc' => 'fb.1.1787000000000.click-id',
            'meta_tracking_consented_at' => now(),
        ]);

        $this->postJson(app(TasteProfileUrlGenerator::class)->metaConsent($lead), ['status' => 'denied'])
            ->assertOk()
            ->assertJson(['updated' => true]);

        $lead->refresh();
        $this->assertNull($lead->meta_fbp);
        $this->assertNull($lead->meta_fbc);
        $this->assertNotNull($lead->meta_tracking_revoked_at);
    }

    private function lead(): HvcoLead
    {
        return HvcoLead::create([
            'email' => 'curious@example.com',
            'first_name' => 'Curious',
            'landing_variant' => 'main',
            'consent_text' => 'Approved.',
            'consent_at' => now(),
            'locale' => 'en_US',
            'funnel_status' => FunnelStatus::HvcoLead,
        ]);
    }
}
