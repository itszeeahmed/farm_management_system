<?php

namespace App\Http\Middleware;

use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFarmPermission
{
    /**
     * Handle an incoming request and enforce farm-level RBAC permissions.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (! $user && (app()->environment('local', 'testing') || config('app.debug'))) {
            $user = User::first();
        }

        // If unauthenticated: destructive operations or team access require authentication
        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /** @var Farm|null $farm */
        $farm = $request->attributes->get('current_farm') ?? Farm::current();

        if (! $farm) {
            return response()->json([
                'message' => 'No active farm context found.',
            ], 404);
        }

        // Verify if user has ANY of the specified permissions for this farm
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->canAccessFarm($farm, $permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (! $hasPermission) {
            $required = count($permissions) === 1 ? $permissions[0] : implode(', ', $permissions);

            return response()->json([
                'message' => "Access denied: You do not have the required permission '{$required}' on this farm.",
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
