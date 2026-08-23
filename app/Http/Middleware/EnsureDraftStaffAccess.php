<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDraftStaffAccess
{
    /**
     * Staff may access the dashboard, profile, monitoring, budgeting (allocation
     * only), cash activity (create/submit drafts), and AR & AP input flows
     * (create + pay; edit/delete stays admin-only).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin() || $request->routeIs(
            'dashboard',
            'profile',
            'monitoring.*',
            'budgeting.*',
            'allokasis.*',
            'cashflows.index',
            'cashflows.create',
            'ar-ap.*',
            'receivables.index',
            'receivables.create',
            'receivables.pay',
            'payables.index',
            'payables.create',
            'payables.pay',
            'payments.index',
            'realisasi.index',
            'realisasi.summary',
            'imports.*',
        )) {
            return $next($request);
        }

        abort(403, 'Staff can only create and manage their own drafts.');
    }
}
