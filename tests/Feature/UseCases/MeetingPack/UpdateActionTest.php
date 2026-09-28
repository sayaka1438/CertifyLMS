<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create([
            'name' => '変更前パック',
        ]);

        $data = [
            'name' => '変更後パック',
            'description' => '変更後のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $plan = app(UpdateAction::class)(
            plan: $plan,
            admin: $admin,
            validated: $data,
        );

        $this->assertSame(MeetingPackStatus::Published, $plan->status);
        $this->assertSame($admin->id, $plan->updated_by_user_id);

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => $data['name'],
            'meeting_count' => $data['meeting_count'],
            'price' => $data['price'],
        ]);
    }
}
