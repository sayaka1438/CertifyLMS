<?php

declare(strict_types=1);

return [
    'admin_kpi_cache_key' => 'dashboard.admin.kpi',
    'admin_completion_rate_cache_key' => 'dashboard.admin.completion_rate',

    // キャッシュの有効期間（秒）
    'admin_kpi_cache_ttl' => (int) env('DASHBOARD_ADMIN_KPI_CACHE_TTL', 300),
    'admin_completion_rate_cache_ttl' => (int) env('DASHBOARD_ADMIN_COMPLETION_RATE_CACHE_TTL', 300),
];
