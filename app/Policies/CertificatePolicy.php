<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\User;

/**
 * Certificate リソースに対する認可ポリシー。
 *
 * - student: 本人の修了証のみダウンロード可
 * - coach: 当該資格の担当コーチのみダウンロード可
 * - admin: すべての修了証をダウンロード可
 */
class CertificatePolicy
{
    public function download(User $user, Certificate $certificate): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Student => $certificate->user_id === $user->id,
            UserRole::Coach => $this->isAssignedCoach($certificate, $user),
        };
    }

    private function isAssignedCoach(Certificate $certificate, User $coach): bool
    {
        $certificate->loadMissing('certification.coaches');

        return $certificate->certification?->coaches->contains('id', $coach->id) ?? false;
    }
}
