<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrchidAdminAccess
{
    /**
     * Usage examples:
     *  \App\Http\Middleware\OrchidAdminAccess::class . ':app.admin'
     *  \App\Http\Middleware\OrchidAdminAccess::class . ':app.admin,app.settings'
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        // Treat API routes as JSON even if Accept header is missing
        $wantsJson = $request->expectsJson() || $request->is('api/*');

        if (! $user) {
            if ($wantsJson) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return redirect()->route('login');
        }

        // Default permission if none passed
        if (empty($permissions)) {
            $permissions = ['app.admin'];
        }

        if (! method_exists($user, 'hasAccess')) {
            if ($wantsJson) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            abort(403, 'Access denied.');
        }

        foreach ($permissions as $permission) {
            if ($user->hasAccess($permission)) {
                return $next($request);
            }
        }

        if ($wantsJson) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        abort(403, 'Access denied.');
    }
}
