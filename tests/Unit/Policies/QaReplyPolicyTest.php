<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\Policies\QaReplyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QaReplyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_reply_for_published_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->create($student, $thread));
    }

    public function test_student_cannot_create_reply_for_unpublished_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->create($student, $thread));
    }

    public function test_assigned_coach_can_create_reply_for_published_certification_thread(): void
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

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->create($coach, $thread));
    }

    public function test_unassigned_coach_cannot_create_reply_for_published_certification_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->create($coach, $thread));
    }

    public function test_assigned_coach_cannot_create_reply_for_unpublished_certification_thread(): void
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

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->create($coach, $thread));
    }

    public function test_admin_cannot_create_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->create($admin, $thread));
    }

    public function test_student_can_update_own_reply_for_published_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($student)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->update($student, $reply));
    }

    public function test_student_cannot_update_other_users_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($otherStudent)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->update($student, $reply));
    }

    public function test_student_cannot_update_own_reply_for_unpublished_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($student)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->update($student, $reply));
    }

    public function test_assigned_coach_can_update_own_reply_for_published_certification_thread(): void
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
        $reply = QaReply::factory()->for($thread, 'thread')->for($coach)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->update($coach, $reply));
    }

    public function test_assigned_coach_cannot_update_other_users_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
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
        $reply = QaReply::factory()->for($thread, 'thread')->for($otherStudent)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->update($coach, $reply));
    }

    public function test_unassigned_coach_cannot_update_own_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($coach)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->update($coach, $reply));
    }

    public function test_assigned_coach_cannot_update_own_reply_for_unpublished_certification_thread(): void
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
        $reply = QaReply::factory()->for($thread, 'thread')->for($coach)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->update($coach, $reply));
    }

    public function test_admin_cannot_update_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->update($admin, $reply));
    }

    public function test_student_can_delete_own_reply_for_published_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($student)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->delete($student, $reply));
    }

    public function test_student_cannot_delete_other_users_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($otherStudent)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->delete($student, $reply));
    }

    public function test_student_cannot_delete_own_reply_for_unpublished_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($student)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->delete($student, $reply));
    }

    public function test_assigned_coach_can_delete_own_reply_for_published_certification_thread(): void
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
        $reply = QaReply::factory()->for($thread, 'thread')->for($coach)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->delete($coach, $reply));
    }

    public function test_assigned_coach_cannot_delete_other_users_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
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
        $reply = QaReply::factory()->for($thread, 'thread')->for($otherStudent)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->delete($coach, $reply));
    }

    public function test_unassigned_coach_cannot_delete_own_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->for($coach)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->delete($coach, $reply));
    }

    public function test_assigned_coach_cannot_delete_own_reply_for_unpublished_certification_thread(): void
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
        $reply = QaReply::factory()->for($thread, 'thread')->for($coach)->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->delete($coach, $reply));
    }

    public function test_admin_can_delete_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()->for($certification)->create();
        $reply = QaReply::factory()->for($thread, 'thread')->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->delete($admin, $reply));
    }
}
