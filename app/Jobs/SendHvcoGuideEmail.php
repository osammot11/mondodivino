<?php

namespace App\Jobs;

use App\Models\HvcoLead;
use App\Services\BrevoClient;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendHvcoGuideEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $leadId) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(BrevoClient $brevo, TasteProfileUrlGenerator $urls): void
    {
        $lead = HvcoLead::findOrFail($this->leadId);
        $brevo->sendGuide($lead, $urls->profile($lead));
        $lead->update(['guide_email_requested_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Brevo HVCO guide email exhausted retries.', ['lead_id' => $this->leadId, 'exception' => $exception::class]);
    }
}
