<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsProfile\StoreAvatarRequest;
use App\UseCases\SettingsProfile\DestroyAvatarAction;
use App\UseCases\SettingsProfile\StoreAvatarAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
    public function store(StoreAvatarRequest $request, StoreAvatarAction $action): RedirectResponse
    {
        $action(
            user: $request->user(),
            file: $request->file('avatar'),
        );

        return redirect()
            ->route('settings.profile.edit')
            ->with('success', 'アバター画像を更新しました。');
    }

    public function destroy(Request $request, DestroyAvatarAction $action): RedirectResponse
    {
        $action(
            user: $request->user(),
        );

        return redirect()
            ->route('settings.profile.edit')
            ->with('success', 'アバター画像を削除しました。');
    }
}
