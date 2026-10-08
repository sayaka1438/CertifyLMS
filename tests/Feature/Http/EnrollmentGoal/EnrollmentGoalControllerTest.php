<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentGoalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_goal_for_own_enrollment(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $data = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), $data);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '個人学習目標を追加しました。');

        $this->assertDatabaseHas('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'target_date' => $data['target_date'],
        ]);
    }

    public function test_store_forbids_other_students_enrollment(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();

        $data = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), $data);

        $response->assertForbidden();

        $this->assertDatabaseMissing('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'target_date' => $data['target_date'],
        ]);
    }

    public function test_store_forbids_graduated_student(): void
    {
        $student = User::factory()->student()->graduated()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $data = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), $data);

        $response->assertForbidden();

        $this->assertDatabaseMissing('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'target_date' => $data['target_date'],
        ]);
    }

    public function test_edit_allows_owner_student(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $response = $this->actingAs($student)->get(route('enrollment-goals.edit', $goal));

        $response->assertOk();
        $response->assertViewIs('enrollment-goal.edit');
        $response->assertViewHas('goal', $goal);
    }

    public function test_edit_forbids_other_student(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $response = $this->actingAs($student)->get(route('enrollment-goals.edit', $goal));

        $response->assertForbidden();
    }

    public function test_update_updates_own_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $data = [
            'title' => 'Laravelの応用を習得する',
            'description' => '個人学習目標を更新する',
            'target_date' => '2026-11-30',
        ];

        $response = $this->actingAs($student)->patch(route('enrollment-goals.update', $goal), $data);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '個人学習目標を更新しました。');

        $this->assertDatabaseHas('enrollment_goals', [
            'id' => $goal->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'target_date' => $data['target_date'],
        ]);
    }

    public function test_update_forbids_other_students_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $data = [
            'title' => 'Laravelの応用を習得する',
            'description' => '個人学習目標を更新する',
            'target_date' => '2026-11-30',
        ];

        $response = $this->actingAs($student)->patch(route('enrollment-goals.update', $goal), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('enrollment_goals', [
            'id' => $goal->id,
            'title' => $goal->title,
            'description' => $goal->description,
            'target_date' => $goal->target_date->toDateString(),
        ]);
    }

    public function test_destroy_deletes_own_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $response = $this->actingAs($student)->delete(route('enrollment-goals.destroy', $goal));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '個人学習目標を削除しました。');

        $this->assertDatabaseMissing('enrollment_goals', [
            'id' => $goal->id,
        ]);
    }

    public function test_destroy_forbids_other_students_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $response = $this->actingAs($student)->delete(route('enrollment-goals.destroy', $goal));

        $response->assertForbidden();

        $this->assertDatabaseHas('enrollment_goals', [
            'id' => $goal->id,
        ]);
    }

    public function test_mark_achieved_marks_own_goal_as_achieved(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $response = $this->actingAs($student)->post(route('enrollment-goals.markAchieved', $goal));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '個人学習目標を達成済みにしました。');

        $this->assertNotNull($goal->fresh()->achieved_at);
    }

    public function test_mark_achieved_forbids_other_students_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $response = $this->actingAs($student)->post(route('enrollment-goals.markAchieved', $goal));

        $response->assertForbidden();

        $this->assertNull($goal->fresh()->achieved_at);
    }

    public function test_unmark_achieved_marks_own_goal_as_unachieved(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->achieved()->create();

        $response = $this->actingAs($student)->delete(route('enrollment-goals.unmarkAchieved', $goal));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success', '個人学習目標を未達成に戻しました。');

        $this->assertNull($goal->fresh()->achieved_at);
    }

    public function test_unmark_achieved_forbids_other_students_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->achieved()->create();

        $response = $this->actingAs($student)->delete(route('enrollment-goals.unmarkAchieved', $goal));

        $response->assertForbidden();

        $this->assertNotNull($goal->fresh()->achieved_at);
    }

    public function test_coach_cannot_create_goal(): void
    {
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $data = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $response = $this->actingAs($coach)->post(route('enrollments.goals.store', $enrollment), $data);

        $response->assertForbidden();

        $this->assertDatabaseMissing('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'title' => $data['title'],
        ]);
    }

    public function test_admin_cannot_create_goal(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $data = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $response = $this->actingAs($admin)->post(route('enrollments.goals.store', $enrollment), $data);

        $response->assertForbidden();

        $this->assertDatabaseMissing('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'title' => $data['title'],
        ]);
    }
}
