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

        app(CompletePaymentAction::class)(
            payment: $payment,
            stripePaymentIntentId: 'pi_test_completed',
        );

        $payment->refresh();

        $this->assertSame(PaymentStatus::Succeeded, $payment->status);
        $this->assertSame('pi_test_completed', $payment->stripe_payment_intent_id);
        $this->assertNotNull($payment->paid_at);

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

        app(CompletePaymentAction::class)(
            payment: $payment,
            stripePaymentIntentId: 'pi_test_completed',
        );

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

        $action(
            payment: $payment,
            stripePaymentIntentId: 'pi_test_completed',
        );
        $action(
            payment: $payment,
            stripePaymentIntentId: 'pi_test_completed',
        );

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
