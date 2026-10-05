<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarkAsReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_mark_own_notification_as_read(): void
    {
        $student = User::factory()->student()->create();

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'data' => [
                'title' => 'テスト通知',
                'message' => 'テスト用の通知です。',
            ],
            'read_at' => null,
        ]);

        $this->actingAs($student)->post(route('notifications.markAsRead', $notification));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_student_cannot_mark_another_users_notification_as_read(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $otherStudent->id,
            'data' => [
                'title' => 'テスト通知',
                'message' => 'テスト用の通知です。',
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($student)->post(route('notifications.markAsRead', $notification));

        $response->assertStatus(404);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_redirects_to_notification_url_after_marking_as_read(): void
    {
        $student = User::factory()->student()->create();

        $url = route('qa-board.show', 'test-thread-id');

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'data' => [
                'title' => 'テスト通知',
                'message' => 'テスト用の通知です。',
                'url' => $url,
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($student)->post(route('notifications.markAsRead', $notification));

        $response->assertRedirect($url);
    }

    public function test_redirects_to_notification_index_when_notification_has_no_url(): void
    {
        $student = User::factory()->student()->create();

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'data' => [
                'title' => 'テスト通知',
                'message' => 'テスト用の通知です。',
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($student)->post(route('notifications.markAsRead', $notification));

        $response->assertRedirect(route('notifications.index'));
    }
}
