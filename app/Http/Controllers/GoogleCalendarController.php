<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendarService,
    ) {}

    public function redirect(): RedirectResponse
    {
        return redirect()->away(
            $this->googleCalendarService->createAuthUrl()
        );
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            if ($request->filled('error')) {
                throw new \RuntimeException(
                    (string) $request->input('error')
                );
            }

            $state = $request->string('state')->toString();
            $expectedState = (string) $request->session()->pull(
                'google_calendar_oauth_state'
            );

            if (
                $state === ''
                || $expectedState === ''
                || ! hash_equals($expectedState, $state)
            ) {
                throw new \RuntimeException('Invalid OAuth state.');
            }

            $code = $request->string('code')->toString();

            if ($code === '') {
                throw new \RuntimeException('Authorization code is missing.');
            }

            $token = $this->googleCalendarService->fetchAccessToken($code);

            $this->googleCalendarService->saveCredential(
                $request->user(),
                $token,
            );

            return redirect()
                ->route('settings.availability.index')
                ->with('success', 'Googleカレンダーと連携しました。');
        } catch (Throwable $e) {
            Log::error('Google Calendar OAuth connection failed.', [
                'user_id' => $request->user()?->id,
                'exception' => $e,
            ]);

            return redirect()
                ->route('settings.availability.index')
                ->with('error', 'Googleカレンダーとの連携に失敗しました。');
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->googleCredential?->delete();

        return redirect()
            ->route('settings.availability.index')
            ->with('success', 'Googleカレンダーとの連携を解除しました。');
    }
}
