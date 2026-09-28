<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\User;
use App\UseCases\MeetingPack\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_meeting_pack_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => '5回パック',
            'description' => '追加面談5回分のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $plan = app(StoreAction::class)(
            admin: $admin,
            validated: $data,
        );

        $this->assertSame(MeetingPackStatus::Draft, $plan->status);
        $this->assertSame($admin->id, $plan->created_by_user_id);
        $this->assertSame($admin->id, $plan->updated_by_user_id);

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => $data['name'],
            'meeting_count' => $data['meeting_count'],
            'price' => $data['price'],
        ]);
    }
}
