<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Notification\IndexRequest;
use App\UseCases\Notification\IndexAction;
use App\UseCases\Notification\MarkAllAsReadAction;
use App\UseCases\Notification\MarkAsReadAction;
use App\UseCases\Notification\ShowAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(IndexRequest $request, IndexAction $action): View
    {
        $validated = $request->validated();

        $tab = $validated['tab'] ?? 'all';

        $result = $action(
            user: $request->user(),
            tab: $tab,
        );

        return view('notifications.index', [
            'notifications' => $result['notifications'],
            'unreadCount' => $result['unreadCount'],
            'tab' => $tab,
        ]);
    }

    public function show(Request $request, string $notification, ShowAction $action): View
    {
        $notification = $action(
            user: $request->user(),
            notificationId: $notification,
        );

        return view('notifications.show', [
            'notification' => $notification,
        ]);
    }

    public function markAsRead(Request $request, string $notification, MarkAsReadAction $action): RedirectResponse
    {
        $notification = $action(
            user: $request->user(),
            notificationId: $notification,
        );

        $notificationType = $notification->data['notification_type'] ?? null;
        $url = $notification->data['url'] ?? null;

        if ($notificationType === 'admin_announcement') {
            return redirect()
                ->route('notifications.show', $notification->id);
        }

        if ($url !== null) {
            return redirect($url);
        }

        return redirect()->route('notifications.index');
    }

    public function markAllAsRead(Request $request, MarkAllAsReadAction $action): RedirectResponse
    {
        $action(
            user: $request->user(),
        );

        return redirect()->route('notifications.index');
    }
}
