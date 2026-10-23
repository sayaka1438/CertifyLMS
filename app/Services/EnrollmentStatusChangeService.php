<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentStatusLog;
use App\Models\User;

/**
 * Enrollment 状態遷移の監査ログを記録し、管理者ダッシュボードの集計キャッシュを無効化する。
 *
 * 呼出側 Action がトランザクション内で recordStatusChange() を呼ぶ前提。
 * 本 Service 自体はトランザクションを開始しない。
 */
final class EnrollmentStatusChangeService
{
    public function __construct(
        private readonly AdminDashboardCacheService $dashboardCache,
    ) {}

    public function recordStatusChange(
        Enrollment $enrollment,
        ?EnrollmentStatus $fromStatus,
        EnrollmentStatus $toStatus,
        ?User $changedBy,
        ?string $reason = null,
    ): EnrollmentStatusLog {
        $statusLog = $enrollment->statusLogs()->create([
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'changed_by_user_id' => $changedBy?->id,
            'changed_reason' => $reason,
            'changed_at' => now(),
        ]);

        $this->dashboardCache->invalidate();

        return $statusLog;
    }
}
