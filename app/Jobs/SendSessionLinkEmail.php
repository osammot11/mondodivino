<?php

namespace App\Jobs;

use App\Models\HvcoLead;
use App\Services\BrevoClient;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSessionLinkEmail implements ShouldQueue
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
        $lead = HvcoLead::with('tasteProfile')->findOrFail($this->leadId);

        $brevo->sendSessionLink($lead, $urls->nextStep($lead));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Brevo session link email exhausted retries.', ['lead_id' => $this->leadId, 'exception' => $exception::class]);
    }
}
