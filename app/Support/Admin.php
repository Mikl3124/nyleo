<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class Admin
{
    public static function notify(Notification $notification): void
    {
        $users = User::all();
        if ($users->isNotEmpty()) {
            NotificationFacade::send($users, $notification);
        }
    }
}
