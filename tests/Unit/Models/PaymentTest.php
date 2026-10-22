<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PaymentStatus;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relation_returns_owner_user(): void
    {
        $student = User::factory()->student()->create();
        $payment = Payment::factory()->for($student)->create();

        $owner = $payment->user;

        $this->assertTrue($owner->is($student));
    }

    public function test_meeting_pack_relation_returns_related_meeting_pack(): void
    {
        $meetingPack = MeetingPack::factory()->published()->create();
        $payment = Payment::factory()->for($meetingPack)->create();

        $relatedPack = $payment->meetingPack;

        $this->assertTrue($relatedPack->is($meetingPack));
    }

    public function test_status_cast_converts_to_payment_status_enum(): void
    {
        $payment = Payment::factory()->pending()->create();

        $fresh = $payment->fresh();

        $this->assertInstanceOf(
            PaymentStatus::class,
            $fresh->status,
        );
        $this->assertSame(PaymentStatus::Pending, $fresh->status);
    }

    public function test_quantity_amount_and_completed_at_casts(): void
    {
        $payment = Payment::factory()->completed()->create([
            'quantity' => 5,
            'amount' => 12000,
            'completed_at' => '2026-10-20 10:00:00',
        ]);

        $fresh = $payment->fresh();

        $this->assertIsInt($fresh->quantity);
        $this->assertIsInt($fresh->amount);
        $this->assertInstanceOf(Carbon::class, $fresh->completed_at);
    }
}
