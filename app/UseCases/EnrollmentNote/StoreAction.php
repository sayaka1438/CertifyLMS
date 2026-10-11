<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;

final class StoreAction
{
    /**
     * @param array{
     *     body: string,
     * } $validated
     */
    public function __invoke(Enrollment $enrollment, User $user, array $validated): EnrollmentNote
    {
        $note = $enrollment->notes()->create([
            'created_by_user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        return $note;
    }
}
