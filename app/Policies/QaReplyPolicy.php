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
    public function create(User $user, QaThread $qaThread): bool
    {
        $qaThread->loadMissing('certification.coaches');

        return match ($user->role) {
            UserRole::Student => $qaThread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $qaThread->certification->status === CertificationStatus::Published && $qaThread->certification->coaches->contains('id', $user->id),
            default => false,
        };
    }

    public function update(User $user, QaReply $qaReply): bool
    {
        $qaReply->loadMissing('thread.certification.coaches');

        return match ($user->role) {
            UserRole::Student => $qaReply->user_id === $user->id
                && $qaReply->thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $qaReply->user_id === $user->id
                && $qaReply->thread->certification->status === CertificationStatus::Published
                && $qaReply->thread->certification->coaches->contains('id', $user->id),
            default => false,
        };
    }

    public function delete(User $user, QaReply $qaReply): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $qaReply->loadMissing('thread.certification.coaches');

        return match ($user->role) {
            UserRole::Student => $qaReply->user_id === $user->id
                && $qaReply->thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $qaReply->user_id === $user->id
                && $qaReply->thread->certification->status === CertificationStatus::Published
                && $qaReply->thread->certification->coaches->contains('id', $user->id),
            default => false,
        };
    }
}
