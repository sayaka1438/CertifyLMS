<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_plan_index(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertViewIs('plan.management.index');
        $response->assertViewHas('plans');
    }

    public function test_student_cannot_access_plan_index(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('admin.plans.index'));

        $response->assertForbidden();
    }

    public function test_coach_cannot_access_plan_index(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('admin.plans.index'));

        $response->assertForbidden();
    }
}
