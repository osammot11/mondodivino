<?php

namespace App\Jobs;

use App\Services\CalcomWebhookProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCalcomWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $eventId) {}

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(CalcomWebhookProcessor $processor): void
    {
        $processor->process($this->eventId);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Cal.com webhook processing exhausted retries.', ['event_id' => $this->eventId, 'exception' => $exception::class]);
    }
}
