<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentNote>
 */
class EnrollmentNoteFactory extends Factory
{
    protected $model = EnrollmentNote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'created_by_user_id' => User::factory(),
            'body' => fake()->realText(120),
        ];
    }

    public function fromCoach(): static
    {
        return $this->state(fn () => [
            'created_by_user_id' => User::factory()->state([
                'role' => UserRole::Coach->value,
            ]),
        ]);
    }

    public function fromAdmin(): static
    {
        return $this->state(fn () => [
            'created_by_user_id' => User::factory()->state([
                'role' => UserRole::Admin->value,
            ]),
        ]);
    }
}
