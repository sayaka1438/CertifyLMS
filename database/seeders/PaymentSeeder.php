<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\MeetingPack;
use App\Models\MeetingQuotaTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $fixedStudent = User::query()
            ->where('email', 'student@certify-lms.test')
            ->first();

        $demoStudent = User::query()
            ->where('role', UserRole::Student->value)
            ->where('status', UserStatus::InProgress->value)
            ->whereNotIn('email', [
                'student@certify-lms.test',
                'student-noquota@certify-lms.test',
            ])
            ->orderBy('created_at')
            ->first();

        if ($fixedStudent === null || $demoStudent === null) {
            $this->command?->warn(
                'PaymentSeeder: 対象の受講生が存在しません。先に UserSeeder を実行してください。'
            );

            return;
        }

        $meetingPacks = MeetingPack::query()
            ->published()
            ->ordered()
            ->take(3)
            ->get();

        if ($meetingPacks->count() < 3) {
            $this->command?->warn(
                'PaymentSeeder: 公開中の面談パックが3件未満です。先に MeetingPackSeeder を実行してください。'
            );

            return;
        }

        $succeededPack = $meetingPacks->get(0);
        $pendingPack = $meetingPacks->get(1);
        $failedPack = $meetingPacks->get(2);

        $succeededPayment = Payment::create([
            'user_id' => $fixedStudent->id,
            'meeting_pack_id' => $succeededPack->id,
            'quantity' => $succeededPack->meeting_count,
            'amount' => $succeededPack->price,
            'status' => PaymentStatus::Succeeded->value,
            'stripe_payment_intent_id' => 'pi_seed_succeeded',
            'stripe_checkout_session_id' => 'cs_seed_succeeded',
            'paid_at' => now()->subDays(3),
        ]);

        MeetingQuotaTransaction::create([
            'user_id' => $fixedStudent->id,
            'type' => MeetingQuotaTransactionType::Purchased->value,
            'amount' => $succeededPayment->quantity,
            'related_payment_id' => $succeededPayment->id,
            'occurred_at' => $succeededPayment->paid_at,
        ]);

        Payment::create([
            'user_id' => $fixedStudent->id,
            'meeting_pack_id' => $succeededPack->id,
            'quantity' => $succeededPack->meeting_count,
            'amount' => $succeededPack->price,
            'status' => PaymentStatus::Refunded->value,
            'stripe_payment_intent_id' => 'pi_seed_refunded',
            'stripe_checkout_session_id' => 'cs_seed_refunded',
            'paid_at' => now()->subDays(5),
        ]);

        Payment::create([
            'user_id' => $demoStudent->id,
            'meeting_pack_id' => $pendingPack->id,
            'quantity' => $pendingPack->meeting_count,
            'amount' => $pendingPack->price,
            'status' => PaymentStatus::Pending->value,
            'stripe_checkout_session_id' => 'cs_seed_pending',
            'paid_at' => null,
        ]);

        Payment::create([
            'user_id' => $demoStudent->id,
            'meeting_pack_id' => $failedPack->id,
            'quantity' => $failedPack->meeting_count,
            'amount' => $failedPack->price,
            'status' => PaymentStatus::Failed->value,
            'stripe_checkout_session_id' => 'cs_seed_failed',
            'paid_at' => null,
        ]);
    }
}
