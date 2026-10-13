<?php

declare(strict_types=1);

namespace App\UseCases\Announcement;

use App\Enums\AnnouncementTargetType;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class StoreAction
{
    /**
     * @param array{
     *     title: string,
     *     body: string,
     *     target_type: string,
     *     target_certification_id?: string|null,
     *     target_user_id?: string|null,
     * } $validated
     */
    public function __invoke(User $admin, array $validated): Announcement
    {
        $targetType = AnnouncementTargetType::from($validated['target_type']);

        return DB::transaction(function () use ($admin, $validated, $targetType) {
            $recipients = $this->recipients(
                targetType: $targetType,
                certificationId: $validated['target_certification_id'] ?? null,
                userId: $validated['target_user_id'] ?? null,
            );

            $announcement = Announcement::create([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'target_type' => $targetType,
                'target_certification_id' => $targetType === AnnouncementTargetType::Certification
                    ? ($validated['target_certification_id'] ?? null)
                    : null,
                'target_user_id' => $targetType === AnnouncementTargetType::User
                    ? ($validated['target_user_id'] ?? null)
                    : null,
                'dispatched_count' => $recipients->count(),
                'dispatched_at' => now(),
                'created_by_user_id' => $admin->id,
            ]);

            DB::afterCommit(function () use ($recipients, $announcement): void {
                foreach ($recipients as $recipient) {
                    $recipient->notify(
                        new AdminAnnouncementNotification($announcement),
                    );
                }
            });

            return $announcement;
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients(
        AnnouncementTargetType $targetType,
        ?string $certificationId,
        ?string $userId,
    ): Collection {
        $query = User::query()
            ->where('role', UserRole::Student)
            ->where('status', UserStatus::InProgress);

        return match ($targetType) {
            AnnouncementTargetType::AllStudents => $query->get(),

            AnnouncementTargetType::Certification => $query
                ->whereHas('enrollments', function ($query) use ($certificationId): void {
                    $query
                        ->where('certification_id', $certificationId)
                        ->where('status', EnrollmentStatus::Learning->value);
                })
                ->get(),

            AnnouncementTargetType::User => $query
                ->whereKey($userId)
                ->get(),
        };
    }
}
