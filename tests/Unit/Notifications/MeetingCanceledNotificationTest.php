<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingCanceledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingCanceledNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_via_database_and_mail(): void
    {
        $meeting = Meeting::factory()->create();
        $coach = User::factory()->coach()->create();

        $notification = new MeetingCanceledNotification($meeting);

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

        $notification = new MeetingCanceledNotification($meeting);

        $data = $notification->toArray($coach);

        $this->assertSame(
            [
                'title' => '面談がキャンセルされました。',
                'message' => '2026年10月10日 14:00の面談がキャンセルされました。',
                'notification_type' => 'meeting_canceled',
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

        $notification = new MeetingCanceledNotification($meeting);

        $mail = $notification->toMail($coach);

        $this->assertSame(
            '面談がキャンセルされました。',
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
