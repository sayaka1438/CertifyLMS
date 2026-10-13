<?php

declare(strict_types=1);

namespace App\UseCases\Notification;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

final class ShowAction
{
    public function __invoke(
        User $user,
        string $notificationId,
    ): DatabaseNotification {
        return $user->notifications()
            ->findOrFail($notificationId);
    }
}
