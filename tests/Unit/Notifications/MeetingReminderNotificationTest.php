<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\MeetingReminderWindow;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_via_database_and_mail(): void
    {
        $meeting = Meeting::factory()->create();
        $student = User::factory()->student()->create();

        $notification = new MeetingReminderNotification(
            $meeting,
            MeetingReminderWindow::Eve,
        );

        $this->assertSame(
            ['database', 'mail'],
            $notification->via($student),
        );
    }

    public function test_returns_eve_notification_data(): void
    {
        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
        ]);

        $student = User::factory()->student()->create();

        $notification = new MeetingReminderNotification(
            $meeting,
            MeetingReminderWindow::Eve,
        );

        $data = $notification->toArray($student);

        $this->assertSame(
            [
                'title' => '明日は面談があります。',
                'message' => '2026年10月10日 14:00から面談が予定されています。',
                'notification_type' => 'meeting_reminder_eve',
                'meeting_id' => $meeting->id,
                'url' => route('meetings.show', $meeting),
            ],
            $data,
        );
    }

    public function test_returns_one_hour_before_notification_data(): void
    {
        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
        ]);

        $student = User::factory()->student()->create();

        $notification = new MeetingReminderNotification(
            $meeting,
            MeetingReminderWindow::OneHourBefore,
        );

        $data = $notification->toArray($student);

        $this->assertSame(
            [
                'title' => '1時間後に面談があります。',
                'message' => '2026年10月10日 14:00から面談が予定されています。',
                'notification_type' => 'meeting_reminder_one_hour_before',
                'meeting_id' => $meeting->id,
                'url' => route('meetings.show', $meeting),
            ],
            $data,
        );
    }

    public function test_returns_eve_mail_message(): void
    {
        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
        ]);

        $student = User::factory()->student()->create();

        $notification = new MeetingReminderNotification(
            $meeting,
            MeetingReminderWindow::Eve,
        );

        $mail = $notification->toMail($student);

        $this->assertSame(
            '【Certify LMS】明日は面談があります。',
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

    public function test_returns_one_hour_before_mail_message(): void
    {
        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
        ]);

        $student = User::factory()->student()->create();

        $notification = new MeetingReminderNotification(
            $meeting,
            MeetingReminderWindow::OneHourBefore,
        );

        $mail = $notification->toMail($student);

        $this->assertSame(
            '【Certify LMS】1時間後に面談があります。',
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
