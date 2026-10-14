<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingCanceledNotification;
use App\UseCases\Meeting\CancelAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
}
