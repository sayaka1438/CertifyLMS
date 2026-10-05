<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReservedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingReservedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_via_database_and_mail(): void
    {
        $meeting = Meeting::factory()->create();
        $coach = User::factory()->coach()->create();

        $notification = new MeetingReservedNotification($meeting);

        $this->assertSame(
            ['database', 'mail'],
            $notification->via($coach),
        );
    }

    public function test_returns_notification_data(): void
    {
        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
        ]);

        $coach = User::factory()->coach()->create();

        $notification = new MeetingReservedNotification($meeting);

        $data = $notification->toArray($coach);

        $this->assertSame(
            [
                'title' => '面談が予約されました',
                'message' => '2026年10月10日 14:00の面談が予約されました。',
                'notification_type' => 'meeting_reserved',
                'url' => route('meetings.show', $meeting),
            ],
            $data,
        );
    }

    public function test_returns_mail_message(): void
    {
        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
        ]);

        $coach = User::factory()->coach()->create();

        $notification = new MeetingReservedNotification($meeting);

        $mail = $notification->toMail($coach);

        $this->assertSame(
            '面談が予約されました',
            $mail->subject,
        );

        $this->assertSame(
            'Certify LMS をご利用の皆様へ',
            $mail->greeting,
        );

        $this->assertSame(
            '面談を確認する',
            $mail->actionText,
        );

        $this->assertSame(
            route('meetings.show', $meeting),
            $mail->actionUrl,
        );

        $this->assertSame(
            'Certify LMS 運営チーム',
            $mail->salutation,
        );
    }
}
