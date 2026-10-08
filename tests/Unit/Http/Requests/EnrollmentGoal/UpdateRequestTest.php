<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $data = [
            'title' => 'Laravelの応用を習得する',
            'description' => '個人学習目標を更新する',
            'target_date' => '2026-11-30',
        ];

        $response = $this->actingAs($student)->patch(route('enrollment-goals.update', $goal), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_goals', [
            'id' => $goal->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'target_date' => $data['target_date'],
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->for($enrollment)->create();

        $payload = array_merge([
            'title' => 'Laravelの応用を習得する',
            'description' => '個人学習目標を更新する',
            'target_date' => '2026-11-30',
        ], $overrides);

        $response = $this->actingAs($student)->patchJson(route('enrollment-goals.update', $goal), $payload);

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
