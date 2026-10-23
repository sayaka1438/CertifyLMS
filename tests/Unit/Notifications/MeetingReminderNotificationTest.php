<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\MeetingReminderWindow;
use App\Models\Certification;
use App\Models\Enrollment;
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
                'title' => '前日リマインド',
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
                'title' => '1時間前リマインド',
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
        $certification = Certification::factory()->published()->create([
            'name' => '基本情報技術者試験',
        ]);

        $enrollment = Enrollment::factory()->create([
            'certification_id' => $certification->id,
        ]);

        $meeting = Meeting::factory()
            ->forEnrollment($enrollment)
            ->create([
                'scheduled_at' => '2026-10-10 14:00:00',
                'topic' => '学習方法について相談したい',
            ]);

        $student = User::factory()->student()->create();

        $notification = new MeetingReminderNotification(
            $meeting,
            MeetingReminderWindow::Eve,
        );

        $mail = $notification->toMail($student);

        $this->assertSame(
            '【Certify LMS】前日リマインド',
            $mail->subject,
        );

        $this->assertSame(
            'お世話になっております。',
            $mail->greeting,
        );

        $this->assertSame(
            [
                '日時：2026年10月10日 14:00',
                '資格：基本情報技術者試験',
                '相談内容：学習方法について相談したい',
            ],
            $mail->introLines,
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
        $certification = Certification::factory()->published()->create([
            'name' => '基本情報技術者試験',
        ]);

        $enrollment = Enrollment::factory()->create([
            'certification_id' => $certification->id,
        ]);

        $meeting = Meeting::factory()
            ->forEnrollment($enrollment)
            ->create([
                'scheduled_at' => '2026-10-10 14:00:00',
                'topic' => '学習方法について相談したい',
            ]);

        $student = User::factory()->student()->create();

        $notification = new MeetingReminderNotification(
            $meeting,
            MeetingReminderWindow::OneHourBefore,
        );

        $mail = $notification->toMail($student);

        $this->assertSame(
            '【Certify LMS】1時間前リマインド',
            $mail->subject,
        );

        $this->assertSame(
            'お世話になっております。',
            $mail->greeting,
        );

        $this->assertSame(
            [
                '日時：2026年10月10日 14:00',
                '資格：基本情報技術者試験',
                '相談内容：学習方法について相談したい',
            ],
            $mail->introLines,
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
