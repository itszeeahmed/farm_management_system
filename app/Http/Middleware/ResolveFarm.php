<?php

namespace App\Http\Middleware;

use App\Domain\Organization\Models\Farm;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ResolveFarm
{
    /**
     * Handle an incoming request and resolve the multi-tenant farm context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (! Schema::hasTable('farms')) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        $user = $request->user();
        $farmId = $request->header('X-Farm-Id') ?: $request->input('farm_id');

        $farm = null;

        if ($user) {
            if ($farmId) {
                $targetFarm = Farm::find($farmId);
                if (! $targetFarm) {
                    return response()->json([
                        'message' => 'Farm not found.',
                    ], 404);
                }

                if (! $user->canAccessFarm($targetFarm)) {
                    return response()->json([
                        'message' => 'Unauthorized: You do not have access to this farm.',
                    ], 403);
                }

                $farm = $targetFarm;
            } else {
                $access = $user->farmAccesses()
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', Carbon::now());
                    })
                    ->with('farm')
                    ->first();

                $farm = $access?->farm ?? Farm::first();
            }
        } else {
            // Unauthenticated / public / demo requests
            if ($farmId) {
                $farm = Farm::find($farmId) ?? Farm::first();
            } else {
                $farm = Farm::first();
            }
        }

        if ($farm) {
            app()->instance('current_farm', $farm);
            $request->attributes->set('current_farm', $farm);
        }

        return $next($request);
    }
}
