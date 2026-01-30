<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class LogAuthenticationEvents
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'handleLogin']);
        $events->listen(Logout::class, [self::class, 'handleLogout']);
    }

    public function handleLogin(Login $event): void
    {
        AuditLog::create([
            'user_id'    => $event->user->id,
            'user_name'  => $event->user->name,
            'action'     => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function handleLogout(Logout $event): void
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
}
