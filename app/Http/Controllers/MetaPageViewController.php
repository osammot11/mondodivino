<?php

namespace App\Http\Controllers;

use App\Services\MetaTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaPageViewController extends Controller
{
    public function __invoke(Request $request, MetaTracking $tracking): JsonResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'fbp' => ['nullable', 'string', 'max:255'],
            'fbc' => ['nullable', 'string', 'max:255'],
            'event_source_url' => ['required', 'url:http,https', 'max:2048'],
            'referrer_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        if ($tracking->pixelConfigured() && $tracking->hasConsent($request)) {
            $tracking->trackFromRequest(
                $request,
                'PageView',
                $data['event_id'],
                customData: ['content_name' => 'MondoDiVino HVCO Funnel'],
                sourceUrl: $this->sameOrigin($request, $data['event_source_url']) ? $data['event_source_url'] : url('/'),
                referrerUrl: $data['referrer_url'] ?? null,
            );
        }

        return response()->json(['accepted' => true], 202);
    }

    private function sameOrigin(Request $request, string $url): bool
    {
        $port = parse_url($url, PHP_URL_PORT);
        $origin = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).($port ? ":{$port}" : '');

        return hash_equals($request->getSchemeAndHttpHost(), $origin);
    }
}
