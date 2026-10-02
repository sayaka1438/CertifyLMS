<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_publishes_draft_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $response = $this->actingAs($admin)->post(route('admin.plans.publish', $plan));

        $response->assertRedirect(route('admin.plans.show', $plan));
        $response->assertSessionHas('success', 'プランを公開しました。');

        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_cannot_publish_archived(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->archived()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.plans.publish', $plan));

        $response->assertStatus(409);
        $response->assertJsonPath('message', '下書き状態のプランのみ公開できます。');
    }

    public function test_cannot_publish_already_published(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.plans.publish', $plan));

        $response->assertStatus(409);
    }

    public function test_student_cannot_publish(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($student)->post(route('admin.plans.publish', $plan));

        $response->assertForbidden();
    }

    public function test_coach_cannot_publish(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($coach)->post(route('admin.plans.publish', $plan));

        $response->assertForbidden();
    }
}
