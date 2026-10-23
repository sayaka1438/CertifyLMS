<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MeetingQuota\CreateCheckoutRequest;
use App\Models\MeetingPack;
use App\UseCases\MeetingQuota\CreateCheckoutAction;
use App\UseCases\MeetingQuota\SelectCheckoutAction;
use App\UseCases\MeetingQuota\ShowPaymentAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingQuotaCheckoutController extends Controller
{
    public function select(
        SelectCheckoutAction $action
    ): View {
        $plans = $action();

        return view('meeting-quota.checkout-select', [
            'plans' => $plans,
        ]);
    }

    public function store(
        CreateCheckoutRequest $request,
        CreateCheckoutAction $action
    ): RedirectResponse {
        $validated = $request->validated();

        $meetingPack = MeetingPack::query()
            ->published()
            ->findOrFail($validated['meeting_pack_id']);

        try {
            $session = $action(
                student: $request->user(),
                meetingPack: $meetingPack,
            );

            return redirect()->away($session->url);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('meeting-quota.checkout.select')
                ->with(
                    'error',
                    '決済処理を開始できませんでした。時間をおいて再度お試しください。'
                );
        }
    }

    public function cancel(): RedirectResponse
    {
        return redirect()
            ->route('dashboard.index')
            ->with(
                'info',
                'お支払いがキャンセルされました。'
            );
    }

    public function success(
        Request $request,
        ShowPaymentAction $action,
    ): View {
        $paymentId = $request->query('payment_id');

        if (! is_string($paymentId)) {
            abort(404);
        }

        $payment = $action(
            student: $request->user(),
            paymentId: $paymentId,
        );

        return view('meeting-quota.success', [
            'payment' => $payment,
        ]);
    }
}
