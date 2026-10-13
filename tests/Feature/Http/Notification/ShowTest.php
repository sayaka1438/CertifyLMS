<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_own_notification(): void
    {
        $student = User::factory()->student()->create();

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'data' => [
                'title' => 'メンテナンスのお知らせ',
                'body' => 'メンテナンスを実施します。',
                'message' => 'メンテナンスを実施します。',
                'notification_type' => 'admin_announcement',
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($student)->get(route('notifications.show', $notification));

        $response->assertOk();
        $response->assertViewIs('notifications.show');
        $response->assertViewHas('notification', $notification);
    }

    public function test_student_cannot_view_another_users_notification(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $otherStudent->id,
            'data' => [
                'title' => 'メンテナンスのお知らせ',
                'body' => 'メンテナンスを実施します。',
                'message' => 'メンテナンスを実施します。',
                'notification_type' => 'admin_announcement',
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($student)->get(route('notifications.show', $notification));

        $response->assertNotFound();
    }
}
