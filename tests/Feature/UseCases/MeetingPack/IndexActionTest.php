<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\UseCases\MeetingPack\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_meeting_packs_by_name_keyword(): void
    {
        $matchingPlan = MeetingPack::factory()->create([
            'name' => '5回パック',
        ]);
        $nonMatchingPlan = MeetingPack::factory()->create([
            'name' => '10回パック',
        ]);

        $result = app(IndexAction::class)(
            keyword: '5回',
            status: null,
        );

        $planIds = $result->getCollection()->pluck('id');

        $this->assertTrue($planIds->contains($matchingPlan->id));
        $this->assertFalse($planIds->contains($nonMatchingPlan->id));
    }

    public function test_filters_meeting_packs_by_status(): void
    {
        $publishedPlan = MeetingPack::factory()->published()->create();

        $draftPlan = MeetingPack::factory()->draft()->create();

        $result = app(IndexAction::class)(
            keyword: null,
            status: MeetingPackStatus::Published,
        );

        $planIds = $result->getCollection()->pluck('id');

        $this->assertTrue($planIds->contains($publishedPlan->id));
        $this->assertFalse($planIds->contains($draftPlan->id));
    }

    public function test_meeting_packs_are_paginated(): void
    {
        MeetingPack::factory()->count(21)->create();

        $result = app(IndexAction::class)(
            keyword: null,
            status: null,
        );

        $this->assertSame(21, $result->total());
        $this->assertSame(20, $result->count());
        $this->assertSame(20, $result->perPage());
    }
}
