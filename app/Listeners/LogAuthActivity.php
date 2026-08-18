<?php

namespace App\Listeners;

use App\Models\Activity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthActivity
{
    /**
     * Handle the event.
     */
    public function handle(Login|Failed|Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        $action = match (true) {
            $event instanceof Login => 'login',
            $event instanceof Failed => 'failed',
            $event instanceof Logout => 'logout',
            default => 'auth',
        };

        $description = match ($action) {
            'login' => 'User logged in: '.$event->user->email,
            'failed' => 'Failed login attempt for: '.$event->user->email,
            default => 'User logged out: '.$event->user->email,
        };

        Activity::create([
            'user_id' => $event->user->id,
            'subject_type' => 'user',
            'subject_id' => $event->user->id,
            'action' => $action,
            'description' => $description,
            'properties' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }
}
