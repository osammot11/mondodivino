<?php

namespace Tests\Feature;

use App\Enums\FunnelStatus;
use App\Enums\TasteProfileStatus;
use App\Events\TasteProfileCompleted;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SyncTasteProfileToBrevo;
use App\Models\HvcoLead;
use App\Models\TasteProfile;
use App\Services\BrevoClient;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TasteProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'app.url' => 'http://localhost:8000',
            'services.brevo.api_key' => 'test-key',
            'services.brevo.list_id' => 42,
            'services.brevo.base_url' => 'https://api.brevo.test/v3',
            'funnel.serviceable_states' => [],
            'funnel.minimum_set_budget' => null,
            'funnel.profile_consent_text' => 'Approved profile consent.',
        ]);
    }

    public function test_long_page_renders_approved_copy_ctas_and_draft_for_valid_token(): void
    {
        $lead = $this->lead();

        $this->get($this->profileUrl($lead))
            ->assertOk()
            ->assertSee('Your guide is on its way.')
            ->assertSee('Map Your Personal Italian Wine Profile', false)
            ->assertSee('The Producer-First Selection Method')
            ->assertSee('How Luca Decides Which Bottles Earn a Place in Your Glass')
            ->assertSee('Luca Tastes at the Cellar')
            ->assertSee('Independent Final Decision')
            ->assertSee('Your answers give Luca the exact clues he needs')
            ->assertSee('Tell Luca What Belongs in Your Glass')
            ->assertSee('href="#taste-profile"', false)
            ->assertDontSee('[INSERT', false)
            ->assertDontSee('[USE ONLY IF TRUE', false)
            ->assertDontSee('certified', false)
            ->assertDontSee('thousands', false)
            ->assertDontSee('perfect', false);

        $profile = TasteProfile::sole();
        $this->assertSame(TasteProfileStatus::Draft, $profile->status);
        $this->assertNotNull($profile->started_at);
        $this->assertDatabaseHas('funnel_events', ['name' => 'taste_form_started']);
        $this->assertDatabaseHas('funnel_events', ['name' => 'taste_page_view']);
    }

    public function test_invalid_or_expired_signature_shows_safe_page_without_data(): void
    {
        $lead = $this->lead();
        app(TasteProfileUrlGenerator::class)->profile($lead);

        $this->get("/taste-profile/{$lead->fresh()->taste_profile_token}?expires=1&signature=invalid")
            ->assertStatus(410)
            ->assertSee("Let's get you a fresh link.", false)
            ->assertDontSee($lead->email);
    }

    public function test_secure_url_exposes_neither_email_nor_incrementing_id(): void
    {
        $lead = $this->lead();
        $url = $this->profileUrl($lead);

        $this->assertStringNotContainsString($lead->email, $url);
        $this->assertStringNotContainsString("/{$lead->id}?", $url);
        $this->assertMatchesRegularExpression('#/taste-profile/[A-Za-z0-9]{48}\?#', $url);
    }

    public function test_valid_submit_creates_completed_profile_transitions_lead_and_redirects_to_booking(): void
    {
        Queue::fake();
        Event::fake();
        $lead = $this->lead();
        $url = $this->profileUrl($lead);

        $response = $this->post($url, $this->payload());

        $response->assertRedirectContains('/private-session/');
        $profile = TasteProfile::sole();
        $this->assertSame(TasteProfileStatus::Completed, $profile->status);
        $this->assertSame(['red', 'sparkling'], $profile->wine_styles);
        $this->assertSame(['light_fresh', 'silky_elegant'], $profile->taste_descriptions);
        $this->assertNotNull($profile->completed_at);
        $this->assertNotNull($profile->profile_consented_at);
        $this->assertSame('Approved profile consent.', $profile->profile_consent_text);
        $this->assertSame(FunnelStatus::TasteProfileComplete, $lead->fresh()->funnel_status);
        $this->assertSame('10001', $lead->fresh()->zip);
        Event::assertDispatched(TasteProfileCompleted::class, fn ($event) => $event->tasteProfileId === $profile->id);
        Queue::assertPushed(SyncTasteProfileToBrevo::class, fn ($job) => $job->tasteProfileId === $profile->id && $job->trackCompletion);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Your Taste Profile Has Been Saved &amp; Transmitted Directly to Luca.', false)
            ->assertSee('Book Your Private Italian Wine Selection Session with Luca Gradi')
            ->assertSee('Online scheduling is being configured.')
            ->assertDontSee('cal.com', false);
    }

    public function test_first_profile_completion_queues_meta_complete_registration_with_matching_browser_event(): void
    {
        Queue::fake();
        Event::fake();
        config(['services.meta.pixel_id' => '1234567890', 'services.meta.access_token' => 'token']);
        $lead = $this->lead();

        $response = $this->withUnencryptedCookie('mdv_meta_consent', 'granted')
            ->post($this->profileUrl($lead), $this->payload());

        Queue::assertPushed(SendMetaConversionEvent::class, fn (SendMetaConversionEvent $job): bool => $job->event['event_name'] === 'CompleteRegistration');
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('CompleteRegistration', false);
    }

    public function test_required_fields_maximum_tastes_and_enum_values_are_validated(): void
    {
        Queue::fake();
        $lead = $this->lead();
        $url = $this->profileUrl($lead);

        $this->post($url, ['age_confirmed' => '1'])
            ->assertSessionHasErrors(['first_name', 'last_name', 'city', 'state', 'zip', 'wine_styles', 'taste_descriptions', 'avoidances', 'adventure_level', 'occasions', 'preferred_mix', 'usual_bottle_spend', 'set_budget', 'desired_timing', 'profile_consent']);

        $this->post($url, $this->payload([
            'taste_descriptions' => ['light_fresh', 'silky_elegant', 'rich_full', 'bold_structured', 'crisp_mineral'],
            'adventure_level' => 'impossible-value',
        ]))->assertSessionHasErrors(['taste_descriptions', 'adventure_level']);

        $this->assertDatabaseCount('taste_profiles', 0);
    }

    public function test_missing_age_confirmation_is_validation_error_not_underage_routing(): void
    {
        Queue::fake();
        $lead = $this->lead();

        $this->post($this->profileUrl($lead), [])->assertSessionHasErrors('age_confirmed');

        $this->assertDatabaseCount('taste_profiles', 0);
        $this->assertSame(FunnelStatus::HvcoLead, $lead->fresh()->funnel_status);
    }

    public function test_underage_response_stays_draft_stores_only_age_and_never_opens_booking(): void
    {
        Queue::fake();
        Event::fake();
        $lead = $this->lead();

        $response = $this->post($this->profileUrl($lead), [
            'age_confirmed' => '0',
            'favorite_wines' => 'This must not be stored',
            'additional_notes' => 'This must not be stored',
        ]);

        $response->assertRedirectContains('/not-eligible');
        $profile = TasteProfile::sole();
        $this->assertFalse($profile->age_confirmed);
        $this->assertSame(TasteProfileStatus::Draft, $profile->status);
        $this->assertNull($profile->favorite_wines);
        $this->assertNull($profile->additional_notes);
        $this->assertNull($profile->completed_at);
        $this->assertSame(FunnelStatus::HvcoLead, $lead->fresh()->funnel_status);
        Event::assertNotDispatched(TasteProfileCompleted::class);
        Queue::assertNothingPushed();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('We have not completed a commercial taste profile');
    }

    public function test_repeated_submit_updates_same_profile_without_duplicate_completion_event(): void
    {
        Queue::fake();
        Event::fake();
        $lead = $this->lead();
        $url = $this->profileUrl($lead);

        $this->post($url, $this->payload());
        $completedAt = TasteProfile::sole()->completed_at;
        $this->travel(5)->minutes();
        $this->post($url, $this->payload(['city' => 'Brooklyn', 'adventure_level' => 'hidden_italy']));

        $this->assertDatabaseCount('taste_profiles', 1);
        $this->assertDatabaseCount('funnel_events', 1);
        $profile = TasteProfile::sole();
        $this->assertSame('Brooklyn', $profile->city);
        $this->assertSame('hidden_italy', $profile->adventure_level);
        $this->assertTrue($profile->completed_at->equalTo($completedAt));
        Event::assertDispatchedTimes(TasteProfileCompleted::class, 1);
        Queue::assertPushed(SyncTasteProfileToBrevo::class, 2);
        Queue::assertPushed(SyncTasteProfileToBrevo::class, fn ($job) => ! $job->trackCompletion);
    }

    public function test_configured_unserviceable_state_routes_neutrally_without_shipping_promise(): void
    {
        Queue::fake();
        config(['funnel.serviceable_states' => ['CA']]);
        $lead = $this->lead();

        $response = $this->post($this->profileUrl($lead), $this->payload(['state' => 'NY']));

        $response->assertRedirectContains('/availability');
        $this->assertFalse(TasteProfile::sole()->is_serviceable);
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('not currently able to confirm service availability')
            ->assertDontSee('calendar', false);
    }

    public function test_budget_qualification_flag_is_configurable_without_blocking_completion(): void
    {
        Queue::fake();
        config(['funnel.minimum_set_budget' => '500_749']);
        $lead = $this->lead();

        $this->post($this->profileUrl($lead), $this->payload(['set_budget' => 'under_300']))
            ->assertRedirectContains('/private-session/');

        $this->assertFalse(TasteProfile::sole()->is_budget_qualified);
        $this->assertSame(TasteProfileStatus::Completed, TasteProfile::sole()->status);
    }

    public function test_brevo_job_sends_only_segmentation_fields_and_completion_event(): void
    {
        Http::fake([
            'api.brevo.test/v3/contacts' => Http::response([], 201),
            'api.brevo.test/v3/events' => Http::response([], 204),
        ]);
        $profile = $this->completedProfile();

        (new SyncTasteProfileToBrevo($profile->id))->handle(app(BrevoClient::class));

        Http::assertSent(function (Request $request) use ($profile): bool {
            if (! str_ends_with($request->url(), '/contacts')) {
                return false;
            }

            $json = json_encode($request->data());

            return $request['attributes']['FUNNEL_STATUS'] === 'TASTE_PROFILE_COMPLETE'
                && $request['attributes']['STATE'] === 'NY'
                && ! str_contains($json, (string) $profile->favorite_wines)
                && ! str_contains($json, (string) $profile->italian_memory)
                && ! str_contains($json, (string) $profile->additional_notes);
        });
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/events')
            && $request['event_name'] === 'taste_profile_completed');
    }

    public function test_brevo_failure_is_retried_without_losing_completed_local_profile(): void
    {
        Http::fake(['*' => Http::response(['message' => 'unavailable'], 503)]);
        $profile = $this->completedProfile();
        $job = new SyncTasteProfileToBrevo($profile->id);

        $this->assertSame(5, $job->tries);
        $this->assertSame([30, 120, 600, 1800], $job->backoff());

        try {
            $job->handle(app(BrevoClient::class));
            $this->fail('The Brevo error was not rethrown for retry.');
        } catch (RequestException) {
            $this->assertDatabaseHas('taste_profiles', [
                'id' => $profile->id,
                'status' => TasteProfileStatus::Completed->value,
            ]);
        }
    }

    public function test_profile_submission_is_rate_limited(): void
    {
        Queue::fake();
        $lead = $this->lead();
        $url = $this->profileUrl($lead);

        for ($i = 0; $i < 8; $i++) {
            $this->post($url, $this->payload(['city' => "City {$i}"]))->assertRedirect();
        }

        $this->post($url, $this->payload())->assertTooManyRequests();
        $this->assertDatabaseCount('taste_profiles', 1);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Curious',
            'last_name' => 'Explorer',
            'mobile_phone' => '+1 212 555 0100',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'age_confirmed' => '1',
            'wine_styles' => ['red', 'sparkling'],
            'taste_descriptions' => ['light_fresh', 'silky_elegant'],
            'favorite_wines' => 'Etna Rosso, Verdicchio',
            'avoidances' => ['oaky'],
            'adventure_level' => 'balanced',
            'occasions' => ['everyday_dinners', 'hosting'],
            'preferred_mix' => 'balanced_mix',
            'usual_bottle_spend' => '50_80',
            'set_budget' => '500_749',
            'desired_timing' => 'within_30_days',
            'italian_memory' => 'A quiet dinner in Florence.',
            'additional_notes' => 'Prefers food-friendly bottles.',
            'profile_consent' => '1',
        ], $overrides);
    }

    private function lead(): HvcoLead
    {
        return HvcoLead::create([
            'email' => 'curious@example.com',
            'first_name' => 'Curious',
            'landing_variant' => 'main',
            'consent_text' => 'Approved opt-in consent.',
            'consent_at' => now(),
            'locale' => 'en_US',
            'funnel_status' => FunnelStatus::HvcoLead,
        ]);
    }

    private function profileUrl(HvcoLead $lead): string
    {
        return app(TasteProfileUrlGenerator::class)->profile($lead);
    }

    private function completedProfile(): TasteProfile
    {
        $lead = $this->lead();
        $lead->update(['funnel_status' => FunnelStatus::TasteProfileComplete]);

        return TasteProfile::create([
            'hvco_lead_id' => $lead->id,
            'status' => TasteProfileStatus::Completed,
            'last_name' => 'Explorer',
            'city' => 'New York',
            'state' => 'NY',
            'age_confirmed' => true,
            'wine_styles' => ['red'],
            'taste_descriptions' => ['silky_elegant'],
            'favorite_wines' => 'Private favorite text',
            'avoidances' => ['oaky'],
            'adventure_level' => 'balanced',
            'occasions' => ['everyday_dinners'],
            'preferred_mix' => 'balanced_mix',
            'usual_bottle_spend' => '50_80',
            'set_budget' => '500_749',
            'desired_timing' => 'within_30_days',
            'italian_memory' => 'Private memory text',
            'additional_notes' => 'Private notes text',
            'profile_consent_text' => 'Approved profile consent.',
            'profile_consented_at' => now(),
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }
}
