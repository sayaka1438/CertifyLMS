<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Notification;

use App\Models\User;
use App\UseCases\Notification\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_notifications_for_the_specified_user(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $notification = $this->createNotification($student);
        $otherNotification = $this->createNotification($otherStudent);

        $result = app(IndexAction::class)(
            user: $student,
            tab: 'all',
        );

        $notificationIds = $result['notifications']
            ->getCollection()
            ->pluck('id');

        $this->assertTrue($notificationIds->contains($notification->id));
        $this->assertFalse($notificationIds->contains($otherNotification->id));
    }

    public function test_filters_notifications_by_unread_tab(): void
    {
        $student = User::factory()->student()->create();

        $unreadNotification = $this->createNotification($student);
        $readNotification = $this->createNotification($student, read: true);

        $result = app(IndexAction::class)(
            user: $student,
            tab: 'unread',
        );

        $notificationIds = $result['notifications']
            ->getCollection()
            ->pluck('id');

        $this->assertTrue($notificationIds->contains($unreadNotification->id));
        $this->assertFalse($notificationIds->contains($readNotification->id));
    }

    public function test_returns_unread_notification_count(): void
    {
        $student = User::factory()->student()->create();

        $this->createNotification($student);
        $this->createNotification($student);
        $this->createNotification($student, read: true);

        $result = app(IndexAction::class)(
            user: $student,
            tab: 'all',
        );

        $this->assertSame(2, $result['unreadCount']);
    }

    public function test_notifications_are_paginated(): void
    {
        $student = User::factory()->student()->create();

        for ($i = 0; $i < 21; $i++) {
            $this->createNotification($student);
        }

        $result = app(IndexAction::class)(
            user: $student,
            tab: 'all',
        );

        $this->assertSame(21, $result['notifications']->total());
        $this->assertSame(20, $result['notifications']->count());
        $this->assertSame(20, $result['notifications']->perPage());
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
