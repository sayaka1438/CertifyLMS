<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $data = [
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ];

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'title' => $data['title'],
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $payload = array_merge([
            'title' => 'Laravelの基礎を習得する',
            'description' => '毎日教材を進める',
            'target_date' => '2026-10-31',
        ], $overrides);

        $response = $this->actingAs($student)->postJson(route('enrollments.goals.store', $enrollment), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'title 未指定で 422' => [['title' => ''], 'title'],
            'title 101 文字で 422' => [['title' => str_repeat('a', 101)], 'title'],
            'description 1001 文字で 422' => [['description' => str_repeat('a', 1001)], 'description'],
            'target_date が日付形式でない場合 422' => [['target_date' => 'invalid-date'], 'target_date'],
        ];
    }
}
