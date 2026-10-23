<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\UseCases\Notification\IndexAction;
use App\UseCases\Notification\MarkAllAsReadAction;
use App\UseCases\Notification\MarkAsReadAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, IndexAction $action): JsonResponse
    {
        $tab = $request->query('tab', 'all');

        $result = $action(
            user: $request->user(),
            tab: $tab,
        );

        $notifications = $result['notifications']
            ->getCollection()
            ->map(function ($notification): array {
                $data = is_array($notification->data)
                    ? $notification->data
                    : [];

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? '通知',
                    'message' => $data['message'] ?? ($data['body_preview'] ?? ''),
                    'url' => $data['url'] ?? null,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                    'created_relative' => $notification->created_at?->diffForHumans() ?? '',
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $result['unreadCount'],
        ]);
    }

    public function markAsRead(
        Request $request,
        string $notification,
        MarkAsReadAction $action,
    ): JsonResponse {
        $notification = $action(
            user: $request->user(),
            notificationId: $notification,
        );

        $data = is_array($notification->data)
            ? $notification->data
            : [];

        $url = ($data['notification_type'] ?? null) === 'admin_announcement'
            ? route('notifications.show', $notification)
            : ($data['url'] ?? route('notifications.index'));

        return response()->json([
            'url' => $url,
            'unread_count' => $request->user()
                ->unreadNotifications()
                ->count(),
        ]);
    }

    public function markAllAsRead(
        Request $request,
        MarkAllAsReadAction $action,
    ): JsonResponse {
        $action(user: $request->user());

        return response()->json([
            'unread_count' => 0,
        ]);
    }
}
