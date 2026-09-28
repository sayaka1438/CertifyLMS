<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\MeetingPack;
use App\Models\User;
use App\Policies\MeetingPackPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPackPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_perform_all_meeting_pack_operations(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $plan = MeetingPack::factory()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $plan));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $plan));
        $this->assertTrue($policy->delete($admin, $plan));
        $this->assertTrue($policy->publish($admin, $plan));
        $this->assertTrue($policy->archive($admin, $plan));
        $this->assertTrue($policy->unarchive($admin, $plan));
    }

    public function test_coach_and_student_cannot_manage_meeting_packs(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $student = User::factory()->student()->inProgress()->create();
        $plan = MeetingPack::factory()->create();

        $policy = new MeetingPackPolicy;

        foreach ([$coach, $student] as $user) {
            $this->assertFalse($policy->viewAny($user));
            $this->assertFalse($policy->view($user, $plan));
            $this->assertFalse($policy->create($user));
            $this->assertFalse($policy->update($user, $plan));
            $this->assertFalse($policy->delete($user, $plan));
            $this->assertFalse($policy->publish($user, $plan));
            $this->assertFalse($policy->archive($user, $plan));
            $this->assertFalse($policy->unarchive($user, $plan));
        }
    }
}
