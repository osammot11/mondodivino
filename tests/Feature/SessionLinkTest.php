<?php

namespace Tests\Feature;

use App\Enums\FunnelStatus;
use App\Enums\TasteProfileStatus;
use App\Jobs\SendSessionLinkEmail;
use App\Models\HvcoLead;
use App\Models\TasteProfile;
use App\Services\BrevoClient;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SessionLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'funnel.taste_profile_link_days' => 30,
            'services.brevo.api_key' => 'test-key',
            'services.brevo.list_id' => 42,
            'services.brevo.hvco_template_id' => 77,
            'services.brevo.session_link_template_id' => null,
            'services.brevo.hvco_guide_url' => 'https://example.test/guide.pdf',
            'services.brevo.base_url' => 'https://api.brevo.test/v3',
        ]);
    }

    private function lead(array $attributes = []): HvcoLead
    {
        return HvcoLead::create([
            'email' => 'reader@example.com',
            'first_name' => 'Ava',
            'landing_variant' => 'main',
            'locale' => 'en_US',
            'consent_text' => 'Approved consent.',
            'consent_at' => now(),
            'funnel_status' => FunnelStatus::HvcoLead,
            ...$attributes,
        ]);
    }

    public function test_page_renders_email_form(): void
    {
        $this->get('/session')
            ->assertOk()
            ->assertSee('Book your session with Luca.')
            ->assertSee('SEND MY LINK')
            ->assertSee(route('hvco.landing'), false);
    }

    public function test_known_email_queues_link_and_records_event(): void
    {
        Queue::fake();
        $lead = $this->lead();

        $this->post('/session', ['email' => '  Reader@Example.com '])
            ->assertRedirect(route('session-link.show'))
            ->assertSessionHas('session_link_sent', true);

        Queue::assertPushed(SendSessionLinkEmail::class, fn (SendSessionLinkEmail $job) => $job->leadId === $lead->id);
        $this->assertDatabaseHas('funnel_events', ['hvco_lead_id' => $lead->id, 'name' => 'session_link_requested']);

        $this->get(route('session-link.show'))->assertSee('Your personal link is on its way.');
    }

    public function test_unknown_email_gets_the_same_response_and_sends_nothing(): void
    {
        Queue::fake();

        $this->post('/session', ['email' => 'stranger@example.com'])
            ->assertRedirect(route('session-link.show'))
            ->assertSessionHas('session_link_sent', true);

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('hvco_leads', 0);
    }

    public function test_under_age_lead_gets_neutral_response_and_no_email(): void
    {
        Queue::fake();
        $lead = $this->lead();
        TasteProfile::create(['hvco_lead_id' => $lead->id, 'status' => TasteProfileStatus::Draft, 'age_confirmed' => false, 'started_at' => now()]);

        $this->post('/session', ['email' => 'reader@example.com'])
            ->assertRedirect(route('session-link.show'))
            ->assertSessionHas('session_link_sent', true);

        Queue::assertNothingPushed();
    }

    public function test_repeated_requests_within_cooldown_send_one_email(): void
    {
        Queue::fake();
        $this->lead();

        $this->post('/session', ['email' => 'reader@example.com']);
        $this->post('/session', ['email' => 'reader@example.com']);

        Queue::assertPushed(SendSessionLinkEmail::class, 1);
    }

    public function test_invalid_email_and_honeypot_are_rejected(): void
    {
        Queue::fake();
        $this->lead();

        $this->post('/session', ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->post('/session', ['email' => 'reader@example.com', 'website' => 'spam'])->assertSessionHasErrors('website');

        Queue::assertNothingPushed();
    }

    public function test_next_step_routes_by_profile_state(): void
    {
        $urls = app(TasteProfileUrlGenerator::class);
        $lead = $this->lead();

        $this->assertStringContainsString('/taste-profile/', $urls->nextStep($lead->fresh()));

        $profile = TasteProfile::create(['hvco_lead_id' => $lead->id, 'status' => TasteProfileStatus::Completed, 'age_confirmed' => true, 'started_at' => now()]);
        $this->assertStringContainsString('/private-session/', $urls->nextStep($lead->fresh()));

        $profile->update(['is_serviceable' => false]);
        $this->assertStringContainsString('/availability', $urls->nextStep($lead->fresh()));

        $profile->update(['age_confirmed' => false]);
        $this->assertStringContainsString('/not-eligible', $urls->nextStep($lead->fresh()));
    }

    public function test_job_emails_signed_link_through_brevo(): void
    {
        Http::fake(['https://api.brevo.test/v3/smtp/email' => Http::response(['messageId' => 'x'], 201)]);
        $lead = $this->lead();

        (new SendSessionLinkEmail($lead->id))->handle(app(BrevoClient::class), app(TasteProfileUrlGenerator::class));

        Http::assertSent(function (Request $request) {
            $params = $request['params'];

            return $request->url() === 'https://api.brevo.test/v3/smtp/email'
                && $request['templateId'] === 77
                && $request['to'][0]['email'] === 'reader@example.com'
                && str_contains($params['session_url'], '/taste-profile/')
                && str_contains($params['session_url'], 'signature=')
                && $params['profile_url'] === $params['session_url'];
        });
    }

    public function test_dedicated_template_is_used_when_configured(): void
    {
        config(['services.brevo.session_link_template_id' => 91]);
        Http::fake(['https://api.brevo.test/v3/smtp/email' => Http::response(['messageId' => 'x'], 201)]);
        $lead = $this->lead();

        (new SendSessionLinkEmail($lead->id))->handle(app(BrevoClient::class), app(TasteProfileUrlGenerator::class));

        Http::assertSent(fn (Request $request) => $request['templateId'] === 91);
    }
}
