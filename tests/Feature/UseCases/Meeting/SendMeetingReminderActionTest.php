<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Enums\MeetingReminderWindow;
use App\Models\Meeting;
use App\Models\MeetingReminderDelivery;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use App\UseCases\Meeting\SendMeetingReminderAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendMeetingReminderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reminder_to_student_and_coach_for_reserved_meeting(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create();

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::Eve,
        );

        Notification::assertSentTo(
            $student,
            MeetingReminderNotification::class,
        );

        Notification::assertSentTo(
            $coach,
            MeetingReminderNotification::class,
        );
    }

    public function test_does_not_send_reminder_to_ineligible_user(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->graduated()->create();

        $meeting = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create();

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::Eve,
        );

        Notification::assertSentTo(
            $student,
            MeetingReminderNotification::class,
        );

        Notification::assertNotSentTo(
            $coach,
            MeetingReminderNotification::class,
        );
    }

    public function test_does_not_send_duplicate_reminder_for_same_window(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create();

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::Eve,
        );

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::Eve,
        );

        $this->assertCount(
            1,
            Notification::sent($student, MeetingReminderNotification::class),
        );
        $this->assertCount(
            1,
            Notification::sent($coach, MeetingReminderNotification::class),
        );
    }

    public function test_sends_reminders_for_different_windows(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create();

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::Eve,
        );

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::OneHourBefore,
        );

        $this->assertCount(
            2,
            Notification::sent($student, MeetingReminderNotification::class),
        );
        $this->assertCount(
            2,
            Notification::sent($coach, MeetingReminderNotification::class),
        );
    }

    public function test_does_not_send_reminder_for_non_reserved_meeting(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()->canceled()->forCoach($coach)->forStudent($student)->create();

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::Eve,
        );

        Notification::assertNotSentTo(
            $student,
            MeetingReminderNotification::class,
        );
        Notification::assertNotSentTo(
            $coach,
            MeetingReminderNotification::class,
        );
    }

    public function test_records_delivery_after_sending_reminder(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create();

        app(SendMeetingReminderAction::class)(
            meeting: $meeting,
            window: MeetingReminderWindow::Eve,
        );

        $studentDelivery = MeetingReminderDelivery::query()
            ->where('meeting_id', $meeting->id)
            ->where('user_id', $student->id)
            ->where('window', MeetingReminderWindow::Eve)
            ->firstOrFail();

        $this->assertNotNull($studentDelivery->sent_at);

        $coachDelivery = MeetingReminderDelivery::query()
            ->where('meeting_id', $meeting->id)
            ->where('user_id', $coach->id)
            ->where('window', MeetingReminderWindow::Eve)
            ->firstOrFail();

        $this->assertNotNull($coachDelivery->sent_at);
    }
}
