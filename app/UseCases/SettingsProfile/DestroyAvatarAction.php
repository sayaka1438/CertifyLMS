<?php

declare(strict_types=1);

namespace App\UseCases\SettingsProfile;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DestroyAvatarAction
{
    public function __invoke(User $user): User
    {
        $oldPath = $user->getRawOriginal('avatar_url');

        DB::transaction(function () use ($user, $oldPath) {
            $user->forceFill([
                'avatar_url' => null,
            ])->save();

            if ($oldPath !== null) {
                DB::afterCommit(
                    fn () => Storage::disk('public')->delete($oldPath)
                );
            }
        });

        return $user->refresh();
    }
}
