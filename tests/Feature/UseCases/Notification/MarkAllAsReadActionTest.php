<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Notification;

use App\Models\User;
use App\UseCases\Notification\MarkAllAsReadAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarkAllAsReadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_all_notifications_as_read(): void
    {
        $student = User::factory()->student()->create();

        $notification1 = $this->createNotification($student);
        $notification2 = $this->createNotification($student);

        app(MarkAllAsReadAction::class)($student);

        $this->assertNotNull($notification1->fresh()->read_at);
        $this->assertNotNull($notification2->fresh()->read_at);
    }

    public function test_does_not_mark_another_users_notifications_as_read(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $notification = $this->createNotification($student);
        $otherNotification = $this->createNotification($otherStudent);

        app(MarkAllAsReadAction::class)($student);

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    private function createNotification(User $user): DatabaseNotification
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
            'read_at' => null,
        ]);
    }
}
