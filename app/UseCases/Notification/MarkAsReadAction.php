<?php

declare(strict_types=1);

namespace App\UseCases\Notification;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

final class MarkAsReadAction
{
    public function __invoke(
        User $user,
        string $notificationId,
    ): DatabaseNotification {
        $notification = $user->notifications()
            ->findOrFail($notificationId);

        $notification->markAsRead();

        return $notification;
    }
}
