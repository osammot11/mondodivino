<?php

namespace App\Http\Controllers;

use App\Services\MetaTracking;
use App\Services\TasteProfileUrlGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaConsentController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        TasteProfileUrlGenerator $urls,
        MetaTracking $tracking,
    ): JsonResponse {
        $lead = $urls->resolve($request, $token);
        abort_unless($lead, 404);

        $data = $request->validate([
            'status' => ['required', 'in:granted,denied'],
            'fbp' => ['nullable', 'string', 'max:255'],
            'fbc' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === 'granted') {
            $lead->update([
                'meta_fbp' => $tracking->identifier($data['fbp'] ?? null) ?: $lead->meta_fbp,
                'meta_fbc' => $tracking->identifier($data['fbc'] ?? null) ?: $lead->meta_fbc,
                'meta_tracking_consented_at' => $lead->meta_tracking_consented_at ?? now(),
                'meta_tracking_revoked_at' => null,
            ]);
        } else {
            $lead->update([
                'meta_fbp' => null,
                'meta_fbc' => null,
                'meta_tracking_revoked_at' => now(),
            ]);
        }

        return response()->json(['updated' => true]);
    }
}
