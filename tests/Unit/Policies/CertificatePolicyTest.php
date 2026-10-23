<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certificate;
use App\Models\Certification;
use App\Models\CertificationCoachAssignment;
use App\Models\Enrollment;
use App\Models\User;
use App\Policies\CertificatePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificatePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_role_branching(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($student)->for($certification)->create();
        $certificate = Certificate::factory()->forEnrollment($enrollment)->create();
        $policy = new CertificatePolicy;

        $this->assertTrue($policy->download($admin, $certificate));
        $this->assertTrue($policy->download($student, $certificate));
        $this->assertFalse($policy->download($other, $certificate), '他人の certificate は download 不可');
    }

    public function test_coach_can_download_only_assigned_certification(): void
    {
        $coach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $assignedCert = Certification::factory()->published()->create();
        $otherCert = Certification::factory()->published()->create();

        CertificationCoachAssignment::create([
            'id' => (string) Str::ulid(),
            'certification_id' => $assignedCert->id,
            'user_id' => $coach->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);

        $assignedEnrollment = Enrollment::factory()
            ->for($student)
            ->for($assignedCert)
            ->create();

        $otherEnrollment = Enrollment::factory()
            ->for($student)
            ->for($otherCert)
            ->create();

        $assignedCertificate = Certificate::factory()
            ->forEnrollment($assignedEnrollment)
            ->create();

        $otherCertificate = Certificate::factory()
            ->forEnrollment($otherEnrollment)
            ->create();

        $policy = new CertificatePolicy;

        $this->assertTrue($policy->download($coach, $assignedCertificate));
        $this->assertFalse(
            $policy->download($coach, $otherCertificate),
            '担当外資格の certificate は download 不可',
        );
    }
}
