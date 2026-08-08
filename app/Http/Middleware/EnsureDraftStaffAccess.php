<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDraftStaffAccess
{
    /**
     * Staff may access only the dashboard, profile, and their draft workflows.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin() || $request->routeIs('dashboard', 'profile', 'allokasis.*', 'payment-requests.*')) {
            return $next($request);
        }

        abort(403, 'Staff can only create and manage their own drafts.');
    }
}
