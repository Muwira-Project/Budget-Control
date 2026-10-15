<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDraftStaffAccess
{
    /**
     * Staff may access the dashboard, profile, monitoring, budgeting (allocation
     * only), cash activity (own manual entries), and transaction imports for
     * cash activity, transfers, receivables, and payables. Master-data imports
     * and administrative export routes remain admin-only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $staffExportPageAllowed = $request->routeIs('exports.page')
            && in_array($request->route('type'), ['receivables', 'payables'], true);

        if ($user?->isAdmin() || $staffExportPageAllowed || $request->routeIs(
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
            'imports.cashflows*',
            'imports.receivables*',
            'imports.payables*',
            'imports.fund-transfers*',
            'exports.cashflows',
            'exports.receivables',
            'exports.payables',
        )) {
            return $next($request);
        }

        abort(403, 'Staff can only create and manage their own drafts.');
    }
}
