<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;

class EnrollmentGoalPolicy
{
    public function create(User $user, Enrollment $enrollment): bool
    {
        return $user->role === UserRole::Student
            && $enrollment->user_id === $user->id;
    }

    public function update(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        return $this->isOwner($user, $enrollmentGoal);
    }

    public function delete(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        return $this->isOwner($user, $enrollmentGoal);
    }

    public function markAchieved(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        return $this->isOwner($user, $enrollmentGoal);
    }

    public function unmarkAchieved(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        return $this->isOwner($user, $enrollmentGoal);
    }

    private function isOwner(User $user, EnrollmentGoal $goal): bool
    {
        return $user->role === UserRole::Student
            && $goal->enrollment->user_id === $user->id;
    }
}
