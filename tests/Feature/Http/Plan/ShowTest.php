<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.show', $plan));

        $response->assertOk();
        $response->assertViewIs('plan.management.show');
        $response->assertViewHas('plan', $plan);
    }

    public function test_student_cannot_view_plan(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.plans.show', $plan));

        $response->assertForbidden();
    }

    public function test_coach_cannot_view_plan(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($coach)->get(route('admin.plans.show', $plan));

        $response->assertForbidden();
    }
}
