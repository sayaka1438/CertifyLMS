<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();

        $data = [
            'name' => '5回パック',
            'description' => '追加面談5回分のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => $data['name'],
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();

        $payload = array_merge([
            'name' => '5回パック',
            'description' => '追加面談5回分のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ], $overrides);

        $response = $this->actingAs($admin)
            ->patchJson(route('admin.meeting-packs.update', $plan), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    public function test_coach_cannot_access(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $data = [
            'name' => '5回パック',
            'description' => '追加面談5回分のパックです。',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $response = $this->actingAs($coach)->patchJson(route('admin.meeting-packs.update', $plan), $data);

        $response->assertForbidden();
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'name 未指定で 422' => [['name' => ''], 'name'],
            'name 101 文字で 422' => [['name' => str_repeat('a', 101)], 'name'],
            'description 2001 文字で 422' => [['description' => str_repeat('a', 2001)], 'description'],

            'meeting_count 未指定で 422' => [['meeting_count' => ''], 'meeting_count'],
            'meeting_count 0 で 422' => [['meeting_count' => 0], 'meeting_count'],
            'meeting_count 101 で 422' => [['meeting_count' => 101], 'meeting_count'],
            'meeting_count 文字列で 422' => [['meeting_count' => 'abc'], 'meeting_count'],

            'price 未指定で 422' => [['price' => ''], 'price'],
            'price -1 で 422' => [['price' => -1], 'price'],
            'price 1000001 で 422' => [['price' => 1000001], 'price'],
            'price 文字列で 422' => [['price' => 'abc'], 'price'],

            'stripe_price_id 256 文字で 422' => [['stripe_price_id' => str_repeat('a', 256)], 'stripe_price_id'],

            'sort_order -1 で 422' => [['sort_order' => -1], 'sort_order'],
            'sort_order 文字列で 422' => [['sort_order' => 'abc'], 'sort_order'],
        ];
    }
}
