<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdminDashboardCacheService
{
    public function __construct(
        private readonly EnrollmentStatsService $stats,
    ) {}

    public function adminKpi(): array
    {
        return Cache::remember(
            config('dashboard.admin_kpi_cache_key'),
            config('dashboard.admin_kpi_cache_ttl'),
            fn () => $this->stats->adminKpi(),
        );
    }

    public function completionRateByCertification(): Collection
    {
        return Cache::remember(
            config('dashboard.admin_completion_rate_cache_key'),
            config('dashboard.admin_completion_rate_cache_ttl'),
            fn () => $this->stats->completionRateByCertification(),
        );
    }

    public function invalidate(): void
    {
        Cache::forget(config('dashboard.admin_kpi_cache_key'));
        Cache::forget(config('dashboard.admin_completion_rate_cache_key'));
    }
}
