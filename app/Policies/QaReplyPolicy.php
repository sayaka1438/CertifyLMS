<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

class QaReplyPolicy
{
    public function create(User $user, QaThread $thread): bool
    {
        $thread->loadMissing('certification.coaches');

        return match ($user->role) {
            UserRole::Student => $thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $thread->certification->status === CertificationStatus::Published && $thread->certification->coaches->contains('id', $user->id),
            default => false,
        };
    }

    public function update(User $user, QaReply $reply): bool
    {
        $reply->loadMissing('thread.certification.coaches');

        return match ($user->role) {
            UserRole::Student => $reply->user_id === $user->id
                && $reply->thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $reply->user_id === $user->id
                && $reply->thread->certification->status === CertificationStatus::Published
                && $reply->thread->certification->coaches->contains('id', $user->id),
            default => false,
        };
    }

    public function delete(User $user, QaReply $reply): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $reply->loadMissing('thread.certification.coaches');

        return match ($user->role) {
            UserRole::Student => $reply->user_id === $user->id
                && $reply->thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $reply->user_id === $user->id
                && $reply->thread->certification->status === CertificationStatus::Published
                && $reply->thread->certification->coaches->contains('id', $user->id),
            default => false,
        };
    }
}
