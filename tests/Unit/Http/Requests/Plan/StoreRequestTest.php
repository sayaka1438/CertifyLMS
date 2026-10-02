<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Plan;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($admin)->post(route('admin.plans.store'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('plans', [
            'name' => $data['name'],
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $admin = User::factory()->admin()->create();

        $payload = array_merge([
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ], $overrides);

        $response = $this->actingAs($admin)->postJson(route('admin.plans.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    public function test_student_cannot_access(): void
    {
        $student = User::factory()->student()->create();

        $data = [
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($student)->post(route('admin.plans.store'), $data);

        $response->assertForbidden();
    }

    public function test_coach_cannot_access(): void
    {
        $coach = User::factory()->coach()->create();

        $data = [
            'name' => '3ヶ月プラン',
            'description' => '3ヶ月間の受講プランです。',
            'duration_days' => 90,
            'default_meeting_quota' => 4,
            'sort_order' => 10,
        ];

        $response = $this->actingAs($coach)->post(route('admin.plans.store'), $data);

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

            'duration_days 未指定で 422' => [['duration_days' => ''], 'duration_days'],
            'duration_days 0 で 422' => [['duration_days' => 0], 'duration_days'],
            'duration_days 3651 で 422' => [['duration_days' => 3651], 'duration_days'],
            'duration_days 文字列で 422' => [['duration_days' => 'abc'], 'duration_days'],

            'default_meeting_quota 未指定で 422' => [['default_meeting_quota' => ''], 'default_meeting_quota'],
            'default_meeting_quota -1 で 422' => [['default_meeting_quota' => -1], 'default_meeting_quota'],
            'default_meeting_quota 1001 で 422' => [['default_meeting_quota' => 1001], 'default_meeting_quota'],
            'default_meeting_quota 文字列で 422' => [['default_meeting_quota' => 'abc'], 'default_meeting_quota'],

            'sort_order -1 で 422' => [['sort_order' => -1], 'sort_order'],
            'sort_order 文字列で 422' => [['sort_order' => 'abc'], 'sort_order'],
        ];
    }
}
