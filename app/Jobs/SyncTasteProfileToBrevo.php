<?php

namespace App\Jobs;

use App\Models\TasteProfile;
use App\Services\BrevoClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTasteProfileToBrevo implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $tasteProfileId, public bool $trackCompletion = true) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(BrevoClient $brevo): void
    {
        $profile = TasteProfile::with('lead')->findOrFail($this->tasteProfileId);
        $brevo->upsertTasteProfile($profile);

        if ($this->trackCompletion) {
            $brevo->trackTasteProfileCompleted($profile);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Brevo taste profile sync exhausted retries.', [
            'taste_profile_id' => $this->tasteProfileId,
            'exception' => $exception::class,
        ]);
    }
}
