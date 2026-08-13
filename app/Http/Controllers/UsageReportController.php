<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\UsageRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageReportController extends Controller
{
    /**
     * Record metered usage against a license's active subscription.
     */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $license = License::query()
            ->with(['plan.price', 'subscription'])
            ->where('key', $validated['key'])
            ->firstOrFail();

        if (! $license->isUsable()) {
            return response()->json(['valid' => false, 'error' => 'License is not active.'], 422);
        }

        if (! $license->plan->isUsageBased()) {
            return response()->json(['valid' => false, 'error' => 'Plan is not usage-based.'], 422);
        }

        $subscription = $license->subscription;

        if ($subscription === null) {
            return response()->json(['valid' => false, 'error' => 'License has no subscription to bill usage against.'], 422);
        }

        UsageRecord::create([
            'user_id' => $license->user_id,
            'subscription_id' => $subscription->id,
            'license_id' => $license->id,
            'plan_id' => $license->plan_id,
            'quantity' => $validated['quantity'],
            'recorded_at' => now(),
        ]);

        return response()->json([
            'valid' => true,
            'recorded' => true,
            'quantity' => $validated['quantity'],
        ]);
    }
}
