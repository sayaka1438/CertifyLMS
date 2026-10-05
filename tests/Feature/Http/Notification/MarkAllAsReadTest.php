<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarkAllAsReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_mark_all_notifications_as_read(): void
    {
        $student = User::factory()->student()->create();

        $notification1 = DatabaseNotification::query()->create([
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

        $notification2 = DatabaseNotification::query()->create([
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

        $this->actingAs($student)->post(route('notifications.markAllAsRead'));

        $this->assertNotNull($notification1->fresh()->read_at);
        $this->assertNotNull($notification2->fresh()->read_at);
    }
}
