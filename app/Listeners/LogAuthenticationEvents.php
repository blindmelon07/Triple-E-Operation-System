<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class LogAuthenticationEvents
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'onLogin']);
        $events->listen(Logout::class, [self::class, 'onLogout']);
        $events->listen(Failed::class, [self::class, 'onFailed']);
    }

    public function onLogin(Login $event): void
    {
        AuditLog::create([
            'user_id'    => $event->user->id,
            'user_name'  => $event->user->name,
            'action'     => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function onLogout(Logout $event): void
    {
        if (!$event->user) {
            return;
        }

        AuditLog::create([
            'user_id'    => $event->user->id,
            'user_name'  => $event->user->name,
            'action'     => 'logout',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function onFailed(Failed $event): void
    {
        // Keep what was typed as the login name (to spot guessing at an account),
        // never the password.
        $attempted = collect($event->credentials)->except(['password'])->first();

        AuditLog::create([
            'user_id'    => $event->user?->id,
            'user_name'  => $event->user?->name ?? $attempted,
            'action'     => 'login_failed',
            'new_values' => [
                'attempted_login' => $attempted,
                'reason'          => $event->user ? 'wrong password' : 'unknown account',
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
