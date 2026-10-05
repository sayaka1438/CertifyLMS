<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_notification_index(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertViewHas('notifications');
        $response->assertViewHas('unreadCount');
        $response->assertViewHas('tab');
    }

    public function test_graduated_student_can_view_notification_index(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $response = $this->actingAs($student)->get(route('notifications.index'));

        $response->assertOk();
    }

    public function test_student_can_only_see_own_notifications(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $notification = $this->createNotification($student);
        $otherNotification = $this->createNotification($otherStudent);

        $response = $this->actingAs($student)->get(route('notifications.index'));

        $notifications = $response->viewData('notifications');

        $notificationIds = $notifications
            ->getCollection()
            ->pluck('id');

        $this->assertTrue($notificationIds->contains($notification->id));
        $this->assertFalse($notificationIds->contains($otherNotification->id));
    }

    public function test_student_can_filter_notifications_by_unread_tab(): void
    {
        $student = User::factory()->student()->create();

        $unreadNotification = $this->createNotification($student);
        $readNotification = $this->createNotification($student, read: true);

        $response = $this->actingAs($student)->get(route('notifications.index', [
            'tab' => 'unread',
        ]));

        $notifications = $response->viewData('notifications');

        $notificationIds = $notifications
            ->getCollection()
            ->pluck('id');

        $this->assertTrue($notificationIds->contains($unreadNotification->id));
        $this->assertFalse($notificationIds->contains($readNotification->id));
    }

    private function createNotification(User $user, bool $read = false): DatabaseNotification
    {
        return DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'title' => 'テスト通知',
                'message' => 'テスト用の通知です。',
            ],
            'read_at' => $read ? now() : null,
        ]);
    }
}
