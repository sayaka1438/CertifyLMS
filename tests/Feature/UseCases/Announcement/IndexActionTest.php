<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Announcement;

use App\Models\Announcement;
use App\UseCases\Announcement\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_announcements_in_latest_dispatch_order(): void
    {
        $oldest = Announcement::factory()->create([
            'dispatched_at' => now()->subDays(3),
        ]);
        $middle = Announcement::factory()->create([
            'dispatched_at' => now()->subDays(2),
        ]);
        $latest = Announcement::factory()->create([
            'dispatched_at' => now()->subDay(),
        ]);

        $result = app(IndexAction::class)();

        $announcementIds = $result->getCollection()->pluck('id');

        $this->assertSame(
            [
                $latest->id,
                $middle->id,
                $oldest->id,
            ],
            $announcementIds->all(),
        );
    }

    public function test_announcements_are_paginated(): void
    {
        Announcement::factory()->count(21)->create();

        $result = app(IndexAction::class)();

        $this->assertSame(21, $result->total());
        $this->assertSame(20, $result->count());
        $this->assertSame(20, $result->perPage());
    }
}
