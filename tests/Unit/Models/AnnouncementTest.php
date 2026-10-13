<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AnnouncementTargetType;
use App\Models\Announcement;
use App\Models\Certification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_certification_relation_returns_certification(): void
    {
        $certification = Certification::factory()->create();
        $announcement = Announcement::factory()->forCertification($certification)->create();

        $targetCertification = $announcement->targetCertification;

        $this->assertTrue($targetCertification->is($certification));
    }

    public function test_target_user_relation_returns_user(): void
    {
        $student = User::factory()->student()->create();
        $announcement = Announcement::factory()->forUser($student)->create();

        $targetUser = $announcement->targetUser;

        $this->assertTrue($targetUser->is($student));
    }

    public function test_created_by_relation_returns_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create([
            'created_by_user_id' => $admin->id,
        ]);

        $creator = $announcement->createdBy;

        $this->assertTrue($creator->is($admin));
    }

    public function test_target_type_cast_converts_to_enum(): void
    {
        $announcement = Announcement::factory()->create();

        $fresh = $announcement->fresh();

        $this->assertInstanceOf(AnnouncementTargetType::class, $fresh->target_type);
        $this->assertSame(AnnouncementTargetType::AllStudents, $fresh->target_type);
    }

    public function test_dispatched_fields_are_cast_correctly(): void
    {
        $announcement = Announcement::factory()->create([
            'dispatched_count' => 5,
            'dispatched_at' => now(),
        ]);

        $fresh = $announcement->fresh();

        $this->assertIsInt($fresh->dispatched_count);
        $this->assertSame(5, $fresh->dispatched_count);
        $this->assertInstanceOf(Carbon::class, $fresh->dispatched_at);
    }
}
