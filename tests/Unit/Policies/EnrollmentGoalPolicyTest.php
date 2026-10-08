<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use App\Policies\EnrollmentGoalPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentGoalPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_goal_for_own_enrollment(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertTrue($policy->create($student, $enrollment));
    }

    public function test_student_cannot_create_goal_for_other_students_enrollment(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertFalse($policy->create($student, $enrollment));
    }

    public function test_coach_cannot_create_goal(): void
    {
        $coach = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertFalse($policy->create($coach, $enrollment));
    }

    public function test_admin_cannot_create_goal(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertFalse($policy->create($admin, $enrollment));
    }

    public function test_student_can_update_own_goal(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertTrue($policy->update($student, $goal));
    }

    public function test_student_cannot_update_other_students_goal(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertFalse($policy->update($student, $goal));
    }

    public function test_student_can_delete_own_goal(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertTrue($policy->delete($student, $goal));
    }

    public function test_student_cannot_delete_other_students_goal(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertFalse($policy->delete($student, $goal));
    }

    public function test_student_can_mark_own_goal_as_achieved(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertTrue($policy->markAchieved($student, $goal));
    }

    public function test_student_cannot_mark_other_students_goal_as_achieved(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertFalse($policy->markAchieved($student, $goal));
    }

    public function test_student_can_unmark_own_achieved_goal(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->achieved()->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertTrue($policy->unmarkAchieved($student, $goal));
    }

    public function test_student_cannot_unmark_other_students_achieved_goal(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($otherStudent)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->achieved()->create();

        $policy = new EnrollmentGoalPolicy;

        $this->assertFalse($policy->unmarkAchieved($student, $goal));
    }
}
