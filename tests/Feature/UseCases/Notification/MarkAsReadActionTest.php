<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Notification;

use App\Models\User;
use App\UseCases\Notification\MarkAsReadAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarkAsReadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_notification_as_read(): void
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

        app(MarkAsReadAction::class)(
            user: $student,
            notificationId: $notification->id,
        );

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_another_users_notification_as_read(): void
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

        $this->expectException(ModelNotFoundException::class);

        app(MarkAsReadAction::class)(
            user: $student,
            notificationId: $notification->id,
        );
    }
}
