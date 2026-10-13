<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Enums\MeetingReminderWindow;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendMeetingRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_eve_command_sends_reminder_to_target_meeting(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00'));

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $this->artisan('notifications:send-meeting-reminders', [
            '--window' => MeetingReminderWindow::Eve->value,
        ])->assertExitCode(0);

        Notification::assertSentTo(
            $student,
            MeetingReminderNotification::class,
            function (MeetingReminderNotification $notification) use ($meeting): bool {
                return $notification->meeting->is($meeting)
                    && $notification->window === MeetingReminderWindow::Eve;
            },
        );

        Notification::assertSentTo(
            $coach,
            MeetingReminderNotification::class,
            function (MeetingReminderNotification $notification) use ($meeting): bool {
                return $notification->meeting->is($meeting)
                    && $notification->window === MeetingReminderWindow::Eve;
            },
        );
    }

    public function test_one_hour_before_command_sends_reminder_to_target_meeting(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00'));

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create([
            'scheduled_at' => now()->addHour(),
        ]);

        $this->artisan('notifications:send-meeting-reminders', [
            '--window' => MeetingReminderWindow::OneHourBefore->value,
        ])->assertExitCode(0);

        Notification::assertSentTo(
            $student,
            MeetingReminderNotification::class,
            function (MeetingReminderNotification $notification) use ($meeting): bool {
                return $notification->meeting->is($meeting)
                    && $notification->window === MeetingReminderWindow::OneHourBefore;
            },
        );

        Notification::assertSentTo(
            $coach,
            MeetingReminderNotification::class,
            function (MeetingReminderNotification $notification) use ($meeting): bool {
                return $notification->meeting->is($meeting)
                    && $notification->window === MeetingReminderWindow::OneHourBefore;
            },
        );
    }

    public function test_command_does_not_send_reminder_to_meeting_outside_target_window(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00'));

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create([
            'scheduled_at' => now()->addDay()->addMinutes(10),
        ]);

        $this->artisan('notifications:send-meeting-reminders', [
            '--window' => MeetingReminderWindow::Eve->value,
        ])->assertExitCode(0);

        Notification::assertNotSentTo(
            $student,
            MeetingReminderNotification::class,
        );

        Notification::assertNotSentTo(
            $coach,
            MeetingReminderNotification::class,
        );
    }

    public function test_command_fails_when_window_is_missing(): void
    {
        $this->artisan('notifications:send-meeting-reminders')
            ->expectsOutput('--window オプションを指定してください。')
            ->assertExitCode(1);
    }

    public function test_command_fails_when_window_is_invalid(): void
    {
        $this->artisan('notifications:send-meeting-reminders', [
            '--window' => 'invalid',
        ])
            ->expectsOutput('--window には eve または one_hour_before を指定してください。')
            ->assertExitCode(1);
    }

    public function test_command_uses_minute_boundary_when_executed_with_seconds(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:37'));

        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()
            ->reserved()
            ->forCoach($coach)
            ->forStudent($student)
            ->create([
                'scheduled_at' => now()->addDay()->startOfMinute(),
            ]);

        $this->artisan('notifications:send-meeting-reminders', [
            '--window' => MeetingReminderWindow::Eve->value,
        ])->assertExitCode(0);

        Notification::assertSentTo(
            $student,
            MeetingReminderNotification::class,
            function (MeetingReminderNotification $notification) use ($meeting): bool {
                return $notification->meeting->is($meeting)
                    && $notification->window === MeetingReminderWindow::Eve;
            },
        );

        Notification::assertSentTo(
            $coach,
            MeetingReminderNotification::class,
            function (MeetingReminderNotification $notification) use ($meeting): bool {
                return $notification->meeting->is($meeting)
                    && $notification->window === MeetingReminderWindow::Eve;
            },
        );
    }
}
