<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Models\User;
use App\UseCases\Plan\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_plan_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $plan = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertSame(PlanStatus::Draft, $plan->status);
        $this->assertSame($admin->id, $plan->created_by_user_id);
        $this->assertSame($admin->id, $plan->updated_by_user_id);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => $data['name'],
            'duration_days' => $data['duration_days'],
            'default_meeting_quota' => $data['default_meeting_quota'],
        ]);
    }
}
