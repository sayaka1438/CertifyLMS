<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

final class NotificationEligibilityService
{
    public function canReceive(User $user): bool
    {
        return $user->status === UserStatus::InProgress
            && in_array(
                $user->role,
                [UserRole::Student, UserRole::Coach],
                true,
            );
    }
}
