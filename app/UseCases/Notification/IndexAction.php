<?php

declare(strict_types=1);

namespace App\UseCases\Notification;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class IndexAction
{
    /**
     * @return array{
     *     notifications: LengthAwarePaginator,
     *     unreadCount: int
     * }
     */
    public function __invoke(
        User $user,
        string $tab,
        int $perPage = 20,
    ): array {
        $query = $user->notifications();

        if ($tab === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $unreadCount = $user
            ->unreadNotifications()
            ->count();

        return [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ];
    }
}
