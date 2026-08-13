<?php

namespace App\Http\Controllers;

use App\Models\License;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $license = $this->resolveLicense($validated['key']);

        if (! $license->isUsable()) {
            return response()->json(['valid' => false, 'error' => 'License is not active.'], 422);
        }

        try {
            $activation = $license->activateFor($validated['domain']);
        } catch (DomainException $e) {
            return response()->json(['valid' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'valid' => true,
            'activated' => $activation->wasRecentlyCreated,
            'license' => $this->licensePayload($license),
            'activation_count' => $license->activations()->count(),
            'activation_limit' => $license->activation_limit,
        ]);
    }

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $license = $this->resolveLicense($validated['key']);

        return response()->json([
            'valid' => $license->isValidFor($validated['domain']),
            'license' => $this->licensePayload($license),
            'activation_count' => $license->activations()->count(),
            'activation_limit' => $license->activation_limit,
        ]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $license = $this->resolveLicense($validated['key']);

        return response()->json([
            'valid' => $license->revokeFor($validated['domain']),
            'license' => $this->licensePayload($license),
            'activation_count' => $license->activations()->count(),
            'activation_limit' => $license->activation_limit,
        ]);
    }

    private function resolveLicense(string $key): License
    {
        return License::query()
            ->with(['plan', 'subscription', 'product'])
            ->where('key', $key)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function licensePayload(License $license): array
    {
        return [
            'key' => $license->key,
            'product' => $license->product->name,
            'status' => $license->is_active ? 'active' : 'inactive',
            'valid_until' => $license->validUntil()?->toIso8601String(),
        ];
    }
}
