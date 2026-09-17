<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\Policies\QaThreadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QaThreadPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_any_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->viewAny($student));
    }

    public function test_coach_can_view_any_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->viewAny($coach));
    }

    public function test_admin_can_view_any_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->viewAny($admin));
    }

    public function test_student_can_view_thread_for_published_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->view($student, $thread));
    }

    public function test_student_cannot_view_thread_for_unpublished_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->view($student, $thread));
    }

    public function test_assigned_coach_can_view_thread_for_published_certification(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->view($coach, $thread));
    }

    public function test_unassigned_coach_cannot_view_thread_for_published_certification(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->view($coach, $thread));
    }

    public function test_assigned_coach_cannot_view_thread_for_unpublished_certification(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $admin = User::factory()->admin()->inProgress()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->view($coach, $thread));
    }

    public function test_admin_can_view_thread_for_unpublished_certification(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->view($admin, $thread));
    }

    public function test_student_can_create_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->create($student));
    }

    public function test_coach_cannot_create_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->create($coach));
    }

    public function test_admin_cannot_create_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->create($admin));
    }

    public function test_student_can_update_own_thread_for_published_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->update($student, $thread));
    }

    public function test_student_cannot_update_other_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($otherStudent)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->update($student, $thread));
    }

    public function test_student_cannot_update_own_thread_for_unpublished_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->update($student, $thread));
    }

    public function test_coach_cannot_update_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->update($coach, $thread));
    }

    public function test_admin_cannot_update_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->update($admin, $thread));
    }

    public function test_student_can_delete_own_thread_without_replies(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->delete($student, $thread));
    }

    public function test_student_cannot_delete_own_thread_with_replies(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();
        QaReply::factory()->for($thread, 'thread')->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->delete($student, $thread));
    }

    public function test_student_cannot_delete_other_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($otherStudent)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->delete($student, $thread));
    }

    public function test_student_cannot_delete_own_thread_for_unpublished_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->delete($student, $thread));
    }

    public function test_admin_can_delete_thread_with_replies(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        QaReply::factory()->for($thread, 'thread')->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->delete($admin, $thread));
    }

    public function test_coach_cannot_delete_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->delete($coach, $thread));
    }

    public function test_student_can_resolve_own_open_thread_for_published_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->resolve($student, $thread));
    }

    public function test_student_cannot_resolve_own_resolved_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->resolved()->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->resolve($student, $thread));
    }

    public function test_student_cannot_resolve_other_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($otherStudent)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->resolve($student, $thread));
    }

    public function test_student_cannot_resolve_own_thread_for_unpublished_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->resolve($student, $thread));
    }

    public function test_coach_cannot_resolve_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->resolve($coach, $thread));
    }

    public function test_admin_cannot_resolve_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->resolve($admin, $thread));
    }

    public function test_student_can_unresolve_own_resolved_thread_for_published_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->resolved()->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->unresolve($student, $thread));
    }

    public function test_student_cannot_unresolve_own_open_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->unresolve($student, $thread));
    }

    public function test_student_cannot_unresolve_other_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->for($otherStudent)->resolved()->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->unresolve($student, $thread));
    }

    public function test_student_cannot_unresolve_own_thread_for_unpublished_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->for($student)->resolved()->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->unresolve($student, $thread));
    }

    public function test_coach_cannot_unresolve_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->resolved()->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->unresolve($coach, $thread));
    }

    public function test_admin_cannot_unresolve_thread(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->resolved()->create();

        $policy = new QaThreadPolicy;
        $this->assertFalse($policy->unresolve($admin, $thread));
    }
}
