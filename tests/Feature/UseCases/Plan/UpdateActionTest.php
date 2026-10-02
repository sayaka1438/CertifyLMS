<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Models\Plan;
use App\Models\User;
use App\UseCases\Plan\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create([
            'name' => '変更前プラン',
        ]);

        $data = [
            'name' => '変更後プラン',
            'description' => '変更後のプランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $plan = app(UpdateAction::class)(
            plan: $plan,
            admin: $admin,
            validated: $data,
        );

        $this->assertSame(PlanStatus::Published, $plan->status);
        $this->assertSame($admin->id, $plan->updated_by_user_id);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => $data['name'],
            'duration_days' => $data['duration_days'],
            'default_meeting_quota' => $data['default_meeting_quota'],
        ]);
    }
}
