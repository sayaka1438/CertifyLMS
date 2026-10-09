<?php

declare(strict_types=1);

namespace App\UseCases\SettingsProfile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class StoreAvatarAction
{
    public function __invoke(User $user, UploadedFile $file): User
    {
        $ulid = (string) Str::ulid();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $path = "avatars/{$ulid}.{$ext}";

        $oldPath = $user->getRawOriginal('avatar_url');

        try {
            Storage::disk('public')->putFileAs(
                'avatars',
                $file,
                "{$ulid}.{$ext}",
            );

            DB::transaction(function () use ($user, $path, $oldPath) {
                $user->forceFill([
                    'avatar_url' => $path,
                ])->save();

                if ($oldPath !== null) {
                    DB::afterCommit(
                        fn () => Storage::disk('public')->delete($oldPath)
                    );
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);

            throw $e;
        }

        return $user->refresh();
    }
}
