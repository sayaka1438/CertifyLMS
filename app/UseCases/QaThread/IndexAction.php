<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class IndexAction
{
    /**
     * @return array{
     *     threads: LengthAwarePaginator,
     *     certifications: Collection<int, Certification>,
     * }
     */
    public function __invoke(User $viewer, array $filter, int $perPage = 20): array
    {
        $query = QaThread::query();

        if ($viewer->role === UserRole::Student) {
            $query->whereHas('certification', function ($q) {
                $q->published();
            });
        }

        if ($viewer->role === UserRole::Coach) {
            $query->whereHas('certification', function ($q) use ($viewer) {
                $q->published()->assignedTo($viewer);
            });
        }

        $query->with(['user', 'certification'])
            ->withCount('replies');

        $query->when(
            $filter['certification_id'] ?? null,
            fn ($q, string $id) => $q->where('certification_id', $id),
        );

        $status = match ($filter['status'] ?? null) {
            'unresolved' => QaThreadStatus::Open,
            'resolved' => QaThreadStatus::Resolved,
            default => null,
        };

        $query->when(
            $status,
            fn ($q, QaThreadStatus $status) => $q->where('status', $status),
        );

        $query->when(
            $filter['keyword'] ?? null,
            fn ($q, string $keyword) => $q->where('body', 'like', '%'.$keyword.'%'),
        );

        $threads = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $certifications = Certification::query();

        if ($viewer->role === UserRole::Student) {
            $certifications->published();
        }

        if ($viewer->role === UserRole::Coach) {
            $certifications->published()->assignedTo($viewer);
        }

        $certifications = $certifications
            ->orderBy('name')
            ->get();

        return [
            'threads' => $threads,
            'certifications' => $certifications,
        ];
    }
}
