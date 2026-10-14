<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Models\Certification;
use App\Models\CoachAvailability;
use App\Models\Enrollment;
use App\Models\User;
use App\UseCases\Meeting\FetchAvailabilityAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FetchAvailabilityActionTest extends TestCase
{
    use RefreshDatabase;

    private function attachCoach(Certification $certification, User $coach): void
    {
        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => User::factory()->admin()->create()->id,
            'assigned_at' => now(),
            'unassigned_at' => null,
        ]);
    }

    public function test_returns_available_slots_for_specified_date(): void
    {
        $certification = Certification::factory()->published()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();

        $this->attachCoach($certification, $coach);

        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
            'certification_id' => $certification->id,
        ]);

        CoachAvailability::factory()
            ->forCoach($coach)
            ->onDay(1)
            ->timeRange('09:00:00', '12:00:00')
            ->create();

        $result = app(FetchAvailabilityAction::class)(
            enrollment: $enrollment,
            date: '2026-06-01',
        );

        $this->assertSame(
            '2026-06-01',
            $result['date']->toDateString(),
        );

        $this->assertCount(3, $result['slots']);

        $this->assertSame(
            '09:00',
            $result['slots']->first()['slot_start']->format('H:i'),
        );

        $this->assertSame(
            1,
            $result['slots']->first()['available_coach_count'],
        );
    }
}
