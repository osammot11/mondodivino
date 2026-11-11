<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BrevoClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncBookingToBrevo implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $bookingId, public string $eventName, public string $eventKey) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(BrevoClient $brevo): void
    {
        $brevo->syncBooking(Booking::with('lead')->findOrFail($this->bookingId), $this->eventName, $this->eventKey);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Brevo booking sync exhausted retries.', ['booking_id' => $this->bookingId, 'event' => $this->eventName, 'exception' => $exception::class]);
    }
}
