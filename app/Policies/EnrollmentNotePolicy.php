<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;

class EnrollmentNotePolicy
{
    public function viewAny(User $user, Enrollment $enrollment): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Coach => $this->isAssignedCoach($enrollment, $user),
            default => false,
        };
    }

    public function create(User $user, Enrollment $enrollment): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Coach => $this->isAssignedCoach($enrollment, $user),
            default => false,
        };
    }

    public function update(User $user, EnrollmentNote $enrollmentNote): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Coach => $this->isAssignedCoach($enrollmentNote->enrollment, $user)
                && $enrollmentNote->created_by_user_id === $user->id,
            default => false,
        };
    }

    public function delete(User $user, EnrollmentNote $enrollmentNote): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Coach => $this->isAssignedCoach($enrollmentNote->enrollment, $user)
                && $enrollmentNote->created_by_user_id === $user->id,
            default => false,
        };
    }

    private function isAssignedCoach(Enrollment $enrollment, User $coach): bool
    {
        $enrollment->loadMissing('certification.coaches');

        return $enrollment->certification?->coaches->contains('id', $coach->id) ?? false;
    }
}
