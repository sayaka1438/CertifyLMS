<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_via_database_and_mail(): void
    {
        $announcement = Announcement::factory()->create();
        $student = User::factory()->student()->create();

        $notification = new AdminAnnouncementNotification($announcement);

        $this->assertSame(
            ['database', 'mail'],
            $notification->via($student),
        );
    }

    public function test_returns_notification_data(): void
    {
        $announcement = Announcement::factory()->create([
            'title' => 'メンテナンスのお知らせ',
            'body' => 'メンテナンスを実施します。',
        ]);
        $student = User::factory()->student()->create();

        $notification = new AdminAnnouncementNotification($announcement);

        $data = $notification->toArray($student);

        $this->assertSame(
            [
                'title' => 'メンテナンスのお知らせ',
                'body' => 'メンテナンスを実施します。',
                'message' => 'メンテナンスを実施します。',
                'notification_type' => 'admin_announcement',
            ],
            $data,
        );
    }

    public function test_returns_mail_message(): void
    {
        $announcement = Announcement::factory()->create([
            'title' => 'メンテナンスのお知らせ',
            'body' => 'メンテナンスを実施します。',
        ]);
        $student = User::factory()->student()->create();

        $notification = new AdminAnnouncementNotification($announcement);

        $mail = $notification->toMail($student);

        $this->assertSame(
            '【Certify LMS】メンテナンスのお知らせ',
            $mail->subject,
        );

        $this->assertSame(
            '管理者からのお知らせ',
            $mail->greeting,
        );

        $this->assertSame(
            'お知らせを確認する',
            $mail->actionText,
        );

        $this->assertSame(
            route('notifications.index'),
            $mail->actionUrl,
        );

        $this->assertSame(
            'Certify LMS 運営チーム',
            $mail->salutation,
        );
    }

    public function test_is_queued_with_retry_settings(): void
    {
        $announcement = Announcement::factory()->create();

        $notification = new AdminAnnouncementNotification($announcement);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(3, $notification->tries);
        $this->assertSame([10, 30], $notification->backoff);
    }
}
