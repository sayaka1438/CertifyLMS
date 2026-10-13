<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Announcement;

use App\Models\Announcement;
use App\Models\Certification;
use App\Models\User;
use App\UseCases\Announcement\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_target_certification_and_creator(): void
    {
        $admin = User::factory()->admin()->create();
        $certification = Certification::factory()->create();

        $announcement = Announcement::factory()
            ->forCertification($certification)
            ->create([
                'created_by_user_id' => $admin->id,
            ]);

        $result = app(ShowAction::class)($announcement);

        $this->assertTrue($result->relationLoaded('targetCertification'));
        $this->assertTrue($result->relationLoaded('createdBy'));

        $this->assertSame($certification->id, $result->targetCertification->id);
        $this->assertSame($admin->id, $result->createdBy->id);
    }

    public function test_loads_target_user_and_creator(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->inProgress()->create();

        $announcement = Announcement::factory()
            ->forUser($student)
            ->create([
                'created_by_user_id' => $admin->id,
            ]);

        $result = app(ShowAction::class)($announcement);

        $this->assertTrue($result->relationLoaded('targetUser'));
        $this->assertTrue($result->relationLoaded('createdBy'));

        $this->assertSame($student->id, $result->targetUser->id);
        $this->assertSame($admin->id, $result->createdBy->id);
    }
}
