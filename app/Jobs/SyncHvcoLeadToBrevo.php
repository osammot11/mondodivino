<?php

namespace App\Jobs;

use App\Enums\FunnelStatus;
use App\Models\HvcoLead;
use App\Services\BrevoClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncHvcoLeadToBrevo implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $leadId) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(BrevoClient $brevo): void
    {
        $lead = HvcoLead::findOrFail($this->leadId);
        $brevo->upsertContact($lead);

        if ($lead->funnel_status === FunnelStatus::HvcoLead) {
            $brevo->trackOptin($lead);
        }
        $lead->update(['brevo_synced_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Brevo HVCO contact sync exhausted retries.', ['lead_id' => $this->leadId, 'exception' => $exception::class]);
    }
}
