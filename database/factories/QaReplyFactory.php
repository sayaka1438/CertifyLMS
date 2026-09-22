<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QaReply>
 */
class QaReplyFactory extends Factory
{
    protected $model = QaReply::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'qa_thread_id' => QaThread::factory(),
            'user_id' => User::factory(),
            'body' => fake()->realText(120),
        ];
    }

    public function fromStudent(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state([
                'role' => UserRole::Student->value,
            ]),
        ]);
    }

    public function fromCoach(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state([
                'role' => UserRole::Coach->value,
            ]),
        ]);
    }
}
