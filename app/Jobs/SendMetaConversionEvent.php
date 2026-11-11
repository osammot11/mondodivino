<?php

namespace App\Jobs;

use App\Services\MetaConversionsApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMetaConversionEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly array $event) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(MetaConversionsApi $api): void
    {
        $api->send($this->event);
    }
}
