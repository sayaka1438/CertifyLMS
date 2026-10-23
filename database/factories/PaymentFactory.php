<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $quantity = fake()->randomElement([1, 3, 5, 10]);
        $amount = $quantity * fake()->numberBetween(2500, 3500);

        return [
            'user_id' => User::factory()->student(),
            'meeting_pack_id' => MeetingPack::factory()->published(),
            'quantity' => $quantity,
            'amount' => $amount,
            'status' => PaymentStatus::Pending->value,
            'stripe_payment_intent_id' => null,
            'stripe_checkout_session_id' => null,
            'paid_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Pending->value,
            'paid_at' => null,
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Succeeded->value,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed->value,
            'paid_at' => null,
        ]);
    }
}
