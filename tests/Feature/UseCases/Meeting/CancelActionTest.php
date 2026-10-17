<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\MeetingStatus;
use App\Models\GoogleCredential;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingCanceledNotification;
use App\Services\GoogleCalendarService;
use App\UseCases\Meeting\CancelAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class CancelActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancels_meeting_refunds_quota_and_notifies_coach(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create([
            'max_meetings' => 5,
        ]);

        $coach = User::factory()->coach()->inProgress()->create();

        $meeting = Meeting::factory()
            ->reserved()
            ->forCoach($coach)
            ->forStudent($student)
            ->create([
                'scheduled_at' => now()->addDays(3)->startOfHour(),
            ]);

        $canceledMeeting = app(CancelAction::class)(
            meeting: $meeting,
            actor: $student,
        );

        $this->assertSame(
            MeetingStatus::Canceled,
            $canceledMeeting->status,
        );

        $this->assertSame(
            $student->id,
            $canceledMeeting->canceled_by_user_id,
        );

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'related_meeting_id' => $meeting->id,
            'type' => MeetingQuotaTransactionType::Refunded->value,
            'amount' => 1,
        ]);

        Notification::assertSentTo(
            $coach,
            MeetingCanceledNotification::class,
        );
    }

    public function test_deletes_google_calendar_event_when_meeting_is_canceled(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create([
            'max_meetings' => 5,
        ]);

        $coach = User::factory()->coach()->inProgress()->create();

        $credential = GoogleCredential::factory()
            ->for($coach, 'user')
            ->create();

        $meeting = Meeting::factory()
            ->reserved()
            ->forCoach($coach)
            ->forStudent($student)
            ->create([
                'scheduled_at' => now()->addDays(3)->startOfHour(),
                'google_calendar_event_id' => 'google-event-123',
            ]);

        $mock = Mockery::mock(GoogleCalendarService::class);

        $mock->shouldReceive('deleteEvent')
            ->once()
            ->with(
                Mockery::on(
                    fn (GoogleCredential $actualCredential) => $actualCredential->is($credential)
                ),
                'google-event-123',
            );

        $this->app->instance(GoogleCalendarService::class, $mock);

        $canceledMeeting = app(CancelAction::class)(
            meeting: $meeting,
            actor: $student,
        );

        $this->assertSame(
            MeetingStatus::Canceled,
            $canceledMeeting->status,
        );
    }

    public function test_cancels_meeting_when_google_calendar_event_deletion_fails(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create([
            'max_meetings' => 5,
        ]);

        $coach = User::factory()->coach()->inProgress()->create();

        GoogleCredential::factory()
            ->for($coach, 'user')
            ->create();

        $meeting = Meeting::factory()
            ->reserved()
            ->forCoach($coach)
            ->forStudent($student)
            ->create([
                'scheduled_at' => now()->addDays(3)->startOfHour(),
                'google_calendar_event_id' => 'google-event-123',
            ]);

        $mock = Mockery::mock(GoogleCalendarService::class);

        $mock->shouldReceive('deleteEvent')
            ->once()
            ->andThrow(new \RuntimeException('Google Calendar API error'));

        $this->app->instance(GoogleCalendarService::class, $mock);

        $canceledMeeting = app(CancelAction::class)(
            meeting: $meeting,
            actor: $student,
        );

        $this->assertSame(
            MeetingStatus::Canceled,
            $canceledMeeting->status,
        );

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'related_meeting_id' => $meeting->id,
            'type' => MeetingQuotaTransactionType::Refunded->value,
            'amount' => 1,
        ]);
    }
}
