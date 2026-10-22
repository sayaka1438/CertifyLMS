<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingQuota;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\MeetingQuotaService;
use App\UseCases\MeetingQuota\CompletePaymentAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletePaymentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completes_payment_and_inserts_purchased_transaction(): void
    {
        $student = User::factory()->student()->create();

        $payment = Payment::factory()->pending()->create([
            'user_id' => $student->id,
            'quantity' => 5,
            'amount' => 12000,
        ]);

        app(CompletePaymentAction::class)($payment);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertNotNull($payment->completed_at);

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'type' => MeetingQuotaTransactionType::Purchased->value,
            'amount' => 5,
            'related_payment_id' => $payment->id,
        ]);
    }

    public function test_completed_purchase_is_reflected_in_remaining_quota(): void
    {
        $student = User::factory()->student()->create([
            'max_meetings' => 2,
        ]);

        $payment = Payment::factory()->pending()->create([
            'user_id' => $student->id,
            'quantity' => 5,
            'amount' => 12000,
        ]);

        $service = app(MeetingQuotaService::class);

        $this->assertSame(2, $service->remaining($student));

        app(CompletePaymentAction::class)($payment);

        $this->assertSame(7, $service->remaining($student));
    }

    public function test_processing_same_payment_twice_does_not_add_quota_twice(): void
    {
        $student = User::factory()->student()->create([
            'max_meetings' => 2,
        ]);

        $payment = Payment::factory()->pending()->create([
            'user_id' => $student->id,
            'quantity' => 5,
            'amount' => 12000,
        ]);

        $action = app(CompletePaymentAction::class);

        $action($payment);
        $action($payment);

        $this->assertDatabaseCount('meeting_quota_transactions', 1);

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'type' => MeetingQuotaTransactionType::Purchased->value,
            'amount' => 5,
            'related_payment_id' => $payment->id,
        ]);

        $this->assertSame(
            7,
            app(MeetingQuotaService::class)->remaining($student)
        );
    }
}
