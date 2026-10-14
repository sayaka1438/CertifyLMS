<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\MeetingStatus;
use App\Models\Certification;
use App\Models\CoachAvailability;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\MeetingReservedNotification;
use App\UseCases\Meeting\StoreAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    private function attachCoach(Certification $certification, User $coach, User $admin): void
    {
        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'unassigned_at' => null,
        ]);
    }

    public function test_stores_meeting_consumes_quota_and_notifies_coach(): void
    {
        Notification::fake();

        $student = User::factory()->student()->inProgress()->create([
            'max_meetings' => 3,
        ]);

        $admin = User::factory()->admin()->create();

        $coach = User::factory()->coach()->inProgress()->create([
            'meeting_url' => 'https://meet.example.com/coach-room',
        ]);

        $certification = Certification::factory()->published()->create();

        $this->attachCoach($certification, $coach, $admin);

        CoachAvailability::factory()
            ->forCoach($coach)
            ->onDay(1)
            ->timeRange('09:00:00', '18:00:00')
            ->create();

        $enrollment = Enrollment::factory()
            ->for($student, 'user')
            ->for($certification)
            ->learning()
            ->create();

        $scheduledAt = now()
            ->startOfDay()
            ->next(Carbon::MONDAY)
            ->setTime(10, 0);

        $meeting = app(StoreAction::class)(
            enrollment: $enrollment,
            scheduledAt: $scheduledAt->format('Y-m-d\TH:i:s'),
            topic: '相談したい',
        );

        $this->assertSame($student->id, $meeting->student_id);
        $this->assertSame($coach->id, $meeting->coach_id);
        $this->assertSame($enrollment->id, $meeting->enrollment_id);
        $this->assertSame(MeetingStatus::Reserved, $meeting->status);
        $this->assertSame('相談したい', $meeting->topic);

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'related_meeting_id' => $meeting->id,
            'type' => MeetingQuotaTransactionType::Consumed->value,
            'amount' => -1,
        ]);

        Notification::assertSentTo(
            $coach,
            MeetingReservedNotification::class,
        );
    }
}
