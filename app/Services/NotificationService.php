<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use App\Notifications\AdminNotification;
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
}
