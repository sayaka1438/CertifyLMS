<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_get_notifications(): void
    {
        $response = $this->getJson(route('api.v1.notifications.index'));

        $response->assertUnauthorized();
    }

    public function test_user_can_get_only_own_notifications(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $notification = $this->createNotification($student);
        $otherNotification = $this->createNotification($otherStudent);

        $response = $this->actingAs($student)->getJson(route('api.v1.notifications.index'));

        $response->assertOk();
        $response->assertJsonPath('unread_count', 1);
        $response->assertJsonFragment([
            'id' => $notification->id,
            'title' => 'テスト通知',
            'message' => 'テスト用の通知です。',
        ]);
        $response->assertJsonMissing([
            'id' => $otherNotification->id,
        ]);
    }

    public function test_user_can_filter_notifications_by_unread_tab(): void
    {
        $student = User::factory()->student()->create();

        $unreadNotification = $this->createNotification($student);
        $readNotification = $this->createNotification($student, read: true);

        $response = $this->actingAs($student)->getJson(route('api.v1.notifications.index', [
            'tab' => 'unread',
        ]));

        $response->assertOk();
        $response->assertJsonPath('unread_count', 1);
        $response->assertJsonFragment([
            'id' => $unreadNotification->id,
        ]);
        $response->assertJsonMissing([
            'id' => $readNotification->id,
        ]);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $student = User::factory()->student()->create();

        $notification = $this->createNotification($student, data: [
            'title' => 'テスト通知',
            'message' => 'テスト用の通知です。',
            'url' => '/test-destination',
        ]);

        $response = $this->actingAs($student)->postJson(route('api.v1.notifications.markAsRead', $notification));

        $response->assertOk();
        $response->assertJsonPath('url', '/test-destination');
        $response->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $notification = $this->createNotification($otherStudent);

        $response = $this->actingAs($student)->postJson(route('api.v1.notifications.markAsRead', $notification));

        $response->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $student = User::factory()->student()->create();

        $notification1 = $this->createNotification($student);
        $notification2 = $this->createNotification($student);

        $response = $this->actingAs($student)->postJson(route('api.v1.notifications.markAllAsRead'));

        $response->assertOk();
        $response->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification1->fresh()->read_at);
        $this->assertNotNull($notification2->fresh()->read_at);
    }

    public function test_marking_admin_announcement_as_read_returns_notification_show_url(): void
    {
        $student = User::factory()->student()->create();

        $notification = $this->createNotification($student, data: [
            'title' => 'メンテナンスのお知らせ',
            'message' => 'メンテナンスを実施します。',
            'notification_type' => 'admin_announcement',
        ]);

        $response = $this->actingAs($student)->postJson(route('api.v1.notifications.markAsRead', $notification));

        $response->assertOk();
        $response->assertJsonPath(
            'url',
            route('notifications.show', $notification),
        );
        $response->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function createNotification(
        User $user,
        bool $read = false,
        ?array $data = null,
    ): DatabaseNotification {
        return DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => $data ?? [
                'title' => 'テスト通知',
                'message' => 'テスト用の通知です。',
            ],
            'read_at' => $read ? now() : null,
        ]);
    }
}
