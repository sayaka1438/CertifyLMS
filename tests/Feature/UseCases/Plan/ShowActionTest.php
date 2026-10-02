<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Models\Plan;
use App\Models\User;
use App\UseCases\Plan\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_creator_updater_and_users(): void
    {
        $creator = User::factory()->admin()->create();
        $updater = User::factory()->admin()->create();

        $plan = Plan::factory()->create([
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $updater->id,
        ]);

        $student = User::factory()->student()->withPlan($plan)->create();

        $result = app(ShowAction::class)($plan);

        $this->assertTrue($result->relationLoaded('createdBy'));
        $this->assertTrue($result->relationLoaded('updatedBy'));
        $this->assertTrue($result->relationLoaded('users'));

        $this->assertSame($creator->id, $result->createdBy->id);
        $this->assertSame($updater->id, $result->updatedBy->id);
        $this->assertTrue($result->users->contains('id', $student->id));
    }
}
