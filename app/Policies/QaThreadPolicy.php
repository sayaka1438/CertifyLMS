<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;

class QaThreadPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array(
            $user->role,
            [UserRole::Admin, UserRole::Coach, UserRole::Student],
            true,
        );
    }

    public function view(User $user, QaThread $thread): bool
    {
        $thread->loadMissing('certification.coaches');

        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Student => $thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $thread->certification->status === CertificationStatus::Published && $thread->certification->coaches->contains('id', $user->id),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Student;
    }

    public function update(User $user, QaThread $thread): bool
    {
        $thread->loadMissing('certification');

        return $user->role === UserRole::Student
            && $thread->user_id === $user->id
            && $thread->certification->status === CertificationStatus::Published;
    }

    public function delete(User $user, QaThread $thread): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $thread->loadMissing('certification');

        return $user->role === UserRole::Student
            && $thread->user_id === $user->id
            && $thread->certification->status === CertificationStatus::Published
            && ! $thread->replies()->exists();
    }

    public function resolve(User $user, QaThread $thread): bool
    {
        $thread->loadMissing('certification');

        return $user->role === UserRole::Student
            && $thread->user_id === $user->id
            && $thread->certification->status === CertificationStatus::Published
            && $thread->status === QaThreadStatus::Open;
    }

    public function unresolve(User $user, QaThread $thread): bool
    {
        $thread->loadMissing('certification');

        return $user->role === UserRole::Student
            && $thread->user_id === $user->id
            && $thread->certification->status === CertificationStatus::Published
            && $thread->status === QaThreadStatus::Resolved;
    }
}
