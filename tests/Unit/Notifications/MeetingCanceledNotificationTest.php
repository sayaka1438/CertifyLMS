<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingCanceledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        $student = User::factory()->student()->create();

        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
            'canceled_by_user_id' => $student->id,
        ]);

        $coach = User::factory()->coach()->create();

        $notification = new MeetingCanceledNotification($meeting);

        $data = $notification->toArray($coach);

        $this->assertSame(
            [
                'title' => "{$student->name}さんから面談がキャンセルされました",
                'message' => '2026年10月10日 14:00の面談がキャンセルされました。',
                'notification_type' => 'meeting_canceled',
                'url' => route('meetings.show', $meeting),
            ],
            $data,
        );
    }

    public function test_returns_mail_message(): void
    {
        $student = User::factory()->student()->create();

        $meeting = Meeting::factory()->create([
            'scheduled_at' => '2026-10-10 14:00:00',
            'canceled_by_user_id' => $student->id,
        ]);

        $coach = User::factory()->coach()->create();

        $notification = new MeetingCanceledNotification($meeting);

        $mail = $notification->toMail($coach);

        $this->assertSame(
            "【Certify LMS】{$student->name}さんから面談がキャンセルされました",
            $mail->subject,
        );

        $this->assertSame(
            'お世話になっております。',
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

    public function test_is_queued_with_retry_settings(): void
    {
        $meeting = Meeting::factory()->create();

        $notification = new MeetingCanceledNotification($meeting);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(3, $notification->tries);
        $this->assertSame([10, 30], $notification->backoff);
    }
}
