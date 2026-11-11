<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MetaConversionsApi
{
    public function configured(): bool
    {
        return trim((string) config('services.meta.pixel_id')) !== ''
            && trim((string) config('services.meta.access_token')) !== '';
    }

    public function send(array $event): ?Response
    {
        if (! $this->configured()) {
            return null;
        }

        $version = trim((string) config('services.meta.graph_api_version', 'v26.0'), '/');
        $pixelId = rawurlencode(trim((string) config('services.meta.pixel_id')));
        $payload = ['data' => [$event]];
        $testCode = trim((string) config('services.meta.test_event_code'));
        if ($testCode !== '') {
            $payload['test_event_code'] = $testCode;
        }

        return Http::withToken((string) config('services.meta.access_token'))
            ->acceptJson()
            ->asJson()
            ->timeout(10)
            ->retry(2, 250)
            ->post(rtrim((string) config('services.meta.graph_base_url'), '/')."/{$version}/{$pixelId}/events", $payload)
            ->throw();
    }
}
