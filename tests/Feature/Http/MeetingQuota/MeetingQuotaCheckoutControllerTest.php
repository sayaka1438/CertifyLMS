<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingQuota;

use App\Enums\PaymentStatus;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Tests\TestCase;

class MeetingQuotaCheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_progress_student_can_view_checkout_page(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $response = $this->actingAs($student)->get(route('meeting-quota.checkout.select'));

        $response->assertOk();
        $response->assertViewIs('meeting-quota.checkout-select');
        $response->assertViewHas('plans');
    }

    public function test_checkout_page_displays_only_published_meeting_packs(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $publishedPack = MeetingPack::factory()->published()->create();
        $draftPack = MeetingPack::factory()->draft()->create();
        $archivedPack = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($student)->get(route('meeting-quota.checkout.select'));

        $response->assertViewHas('plans', function ($plans) use ($publishedPack, $draftPack, $archivedPack) {
            return $plans->contains('id', $publishedPack->id)
                && ! $plans->contains('id', $draftPack->id)
                && ! $plans->contains('id', $archivedPack->id);
        });
    }

    public function test_non_in_progress_student_cannot_view_checkout_page(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $response = $this->actingAs($student)->get(route('meeting-quota.checkout.select'));

        $response->assertForbidden();
    }

    public function test_coach_cannot_view_checkout_page(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $response = $this->actingAs($coach)->get(route('meeting-quota.checkout.select'));

        $response->assertForbidden();
    }

    public function test_admin_cannot_view_checkout_page(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();

        $response = $this->actingAs($admin)->get(route('meeting-quota.checkout.select'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('meeting-quota.checkout.select'));

        $response->assertRedirect(route('login'));
    }

    public function test_payment_cancel_redirects_to_dashboard_with_message(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $response = $this->actingAs($student)->get(route('meeting-quota.checkout.cancel'));

        $response->assertRedirect(route('dashboard.index'));
        $response->assertSessionHas(
            'info',
            'お支払いがキャンセルされました。'
        );
    }

    public function test_student_can_create_stripe_checkout_session_for_published_meeting_pack(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $meetingPack = MeetingPack::factory()->published()->create([
            'meeting_count' => 5,
            'price' => 12000,
        ]);

        $session = new Session('cs_test_checkout');
        $session->url = 'https://checkout.stripe.com/test';

        $stripeService = $this->mock(StripeService::class);

        $stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($session);

        $data = [
            'meeting_pack_id' => $meetingPack->id,
        ];

        $response = $this->actingAs($student)->post(route('meeting-quota.checkout.create'), $data);

        $response->assertRedirect('https://checkout.stripe.com/test');

        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'meeting_pack_id' => $data['meeting_pack_id'],
            'quantity' => 5,
            'amount' => 12000,
            'status' => PaymentStatus::Pending->value,
            'stripe_checkout_session_id' => 'cs_test_checkout',
        ]);
    }

    public function test_student_cannot_purchase_unpublished_meeting_pack(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $draftPack = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($student)->post(route('meeting-quota.checkout.create'), [
            'meeting_pack_id' => $draftPack->id,
        ]);

        $response->assertSessionHasErrors('meeting_pack_id');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_checkout_creation_failure_redirects_back_with_error_message(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $meetingPack = MeetingPack::factory()->published()->create();

        $stripeService = $this->mock(StripeService::class);

        $stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andThrow(new \RuntimeException('Stripe error'));

        $response = $this->actingAs($student)->post(route('meeting-quota.checkout.create'), [
            'meeting_pack_id' => $meetingPack->id,
        ]);

        $response->assertRedirect(route('meeting-quota.checkout.select'));
        $response->assertSessionHas(
            'error',
            '決済処理を開始できませんでした。時間をおいて再度お試しください。'
        );
    }

    public function test_student_can_view_own_payment_success_page(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $payment = Payment::factory()->for($student)->succeeded()->create();

        $response = $this->actingAs($student)->get(route('meeting-quota.success', [
            'payment_id' => $payment->id,
        ]));

        $response->assertOk();
        $response->assertViewIs('meeting-quota.success');
        $response->assertViewHas('payment', $payment);
    }

    public function test_student_cannot_view_another_students_payment_success_page(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();

        $payment = Payment::factory()->for($otherStudent)->succeeded()->create();

        $response = $this->actingAs($student)->get(route('meeting-quota.success', [
            'payment_id' => $payment->id,
        ]));

        $response->assertNotFound();
    }

    public function test_success_page_returns_not_found_when_payment_id_is_missing(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $response = $this->actingAs($student)->get(route('meeting-quota.success'));

        $response->assertNotFound();
    }
}
