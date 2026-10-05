<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\NotificationEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_progress_student_can_receive_notifications(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $result = app(NotificationEligibilityService::class)->canReceive($student);

        $this->assertTrue($result);
    }

    public function test_in_progress_coach_can_receive_notifications(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $result = app(NotificationEligibilityService::class)->canReceive($coach);

        $this->assertTrue($result);
    }

    public function test_graduated_student_cannot_receive_notifications(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $result = app(NotificationEligibilityService::class)->canReceive($student);

        $this->assertFalse($result);
    }

    public function test_withdrawn_student_cannot_receive_notifications(): void
    {
        $student = User::factory()->student()->withdrawn()->create();

        $result = app(NotificationEligibilityService::class)->canReceive($student);

        $this->assertFalse($result);
    }

    public function test_invited_student_cannot_receive_notifications(): void
    {
        $student = User::factory()->student()->invited()->create();

        $result = app(NotificationEligibilityService::class)->canReceive($student);

        $this->assertFalse($result);
    }

    public function test_in_progress_admin_cannot_receive_notifications(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $result = app(NotificationEligibilityService::class)->canReceive($admin);

        $this->assertFalse($result);
    }
}
