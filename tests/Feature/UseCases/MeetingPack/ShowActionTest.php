<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_creator_and_updater(): void
    {
        $creator = User::factory()->admin()->create();
        $updater = User::factory()->admin()->create();

        $plan = MeetingPack::factory()->create([
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $updater->id,
        ]);

        $result = app(ShowAction::class)($plan);

        $this->assertTrue($result->relationLoaded('createdBy'));
        $this->assertTrue($result->relationLoaded('updatedBy'));

        $this->assertSame($creator->id, $result->createdBy->id);
        $this->assertSame($updater->id, $result->updatedBy->id);
    }
}
