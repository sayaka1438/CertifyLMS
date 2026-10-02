<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Models\Plan;
use App\Models\User;
use App\UseCases\Plan\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_plans_by_name_keyword(): void
    {
        $matchingPlan = Plan::factory()->create([
            'name' => '3ヶ月プラン',
        ]);

        $nonMatchingPlan = Plan::factory()->create([
            'name' => '6ヶ月プラン',
        ]);

        $result = app(IndexAction::class)(
            keyword: '3ヶ月',
            status: null,
        );

        $planIds = $result->getCollection()->pluck('id');

        $this->assertTrue($planIds->contains($matchingPlan->id));
        $this->assertFalse($planIds->contains($nonMatchingPlan->id));
    }

    public function test_filters_plans_by_status(): void
    {
        $publishedPlan = Plan::factory()->published()->create();

        $draftPlan = Plan::factory()->draft()->create();

        $result = app(IndexAction::class)(
            keyword: null,
            status: PlanStatus::Published,
        );

        $planIds = $result->getCollection()->pluck('id');

        $this->assertTrue($planIds->contains($publishedPlan->id));
        $this->assertFalse($planIds->contains($draftPlan->id));
    }

    public function test_plans_are_paginated(): void
    {
        Plan::factory()->count(21)->create();

        $result = app(IndexAction::class)(
            keyword: null,
            status: null,
        );

        $this->assertSame(21, $result->total());
        $this->assertSame(20, $result->count());
        $this->assertSame(20, $result->perPage());
    }

    public function test_counts_only_in_progress_users(): void
    {
        $plan = Plan::factory()->create();

        User::factory()->student()->inProgress()->withPlan($plan)->create();

        User::factory()->student()->graduated()->withPlan($plan)->create();

        $result = app(IndexAction::class)(
            keyword: null,
            status: null,
        );

        $resultPlan = $result->getCollection()->firstWhere('id', $plan->id);

        $this->assertSame(1, $resultPlan->users_count);
    }
}
