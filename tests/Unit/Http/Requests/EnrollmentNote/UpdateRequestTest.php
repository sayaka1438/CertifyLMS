<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\EnrollmentNote;

use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_data(): void
    {
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
        ]);

        $data = [
            'body' => '学習状況を更新しました。引き続き順調に進んでいます。',
        ];

        $response = $this->actingAs($coach)->patch(route('enrollment-notes.update', $note), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('enrollment_notes', [
            'id' => $note->id,
            'body' => $data['body'],
        ]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->for($certification)->create();
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = EnrollmentNote::factory()->create([
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
        ]);

        $payload = array_merge([
            'body' => '学習状況を更新しました。引き続き順調に進んでいます。',
        ], $overrides);

        $response = $this->actingAs($coach)->patchJson(route('enrollment-notes.update', $note), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'body 未指定で 422' => [['body' => ''], 'body'],
            'body 2001 文字で 422' => [['body' => str_repeat('a', 2001)], 'body'],
        ];
    }
}
