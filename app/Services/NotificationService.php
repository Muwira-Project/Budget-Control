<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Payable;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\Receivable;
use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send a database notification to every admin user.
     */
    public function notifyAdmins(string $title, string $message, ?string $url = null): void
    {
        $admins = User::query()->where('role', UserRole::Admin)->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new AdminNotification($title, $message, $url));
    }

    /**
     * Notify admins when a realisasi pushes the account actual over its approved allocation.
     */
    public function notifyIfOverBudget(Realisasi $realisasi): void
    {
        $allocation = ProjectAkun::query()
            ->where('project_id', $realisasi->project_id)
            ->where('akun_id', $realisasi->akun_id)
            ->where('status', 'approved')
            ->first();

        if (! $allocation) {
            return;
        }

        $actual = (float) Realisasi::query()
            ->where('project_id', $realisasi->project_id)
            ->where('akun_id', $realisasi->akun_id)
            ->sum('nominal');

        if ($actual <= (float) $allocation->allocation) {
            return;
        }

        $this->notifyAdmins(
            'Over Budget',
            'Actual for account '.$realisasi->akun_id.' exceeds the approved allocation ('.number_format($actual - (float) $allocation->allocation, 0, ',', '.').' over).',
            route('realisasi.index'),
        );
    }

    /**
     * Notify admins when a payable is created/updated and the account is
     * already over its approved allocation (commitment + actual exceeds budget).
     */
    public function notifyIfPayableOverBudget(Payable $payable): void
    {
        if ($payable->project_id === null) {
            return;
        }

        $allocation = ProjectAkun::query()
            ->where('project_id', $payable->project_id)
            ->where('akun_id', $payable->akun_id)
            ->where('status', 'approved')
            ->first();

        if (! $allocation) {
            return;
        }

        $committed = (float) Payable::query()
            ->where('project_id', $payable->project_id)
            ->where('akun_id', $payable->akun_id)
            ->whereColumn('nominal_dibayar', '<', 'nominal')
            ->sum(DB::raw('nominal - nominal_dibayar'));

        $realized = (float) Realisasi::query()
            ->where('project_id', $payable->project_id)
            ->where('akun_id', $payable->akun_id)
            ->sum('nominal');

        $total = $committed + $realized;
        $limit = (float) $allocation->allocation;

        if ($total <= $limit) {
            return;
        }

        $this->notifyAdmins(
            'Over Budget (Payable)',
            'Payable for account '.$payable->akun_id.' pushes commitment + actual above the approved allocation ('.number_format($total - $limit, 0, ',', '.').' over).',
            route('payables.index'),
        );
    }

    /**
     * Notify admins when a receivable is created/updated. This keeps admins
     * aware of manual AR changes (invoice numbers, nominal edits, etc.)
     * recorded in the audit log without blocking flexible edits.
     */
    public function notifyReceivableChanged(Receivable $receivable, string $action): void
    {
        $this->notifyAdmins(
            'AR '.ucfirst($action),
            ($action === 'created' ? 'Receivable created' : 'Receivable updated').' for project #'.$receivable->project_id.' — invoice '.($receivable->nomor_invoice ?? 'n/a').', nominal '.number_format((float) $receivable->nominal, 0, ',', '.').'.',
            route('receivables.index'),
        );
    }
}
