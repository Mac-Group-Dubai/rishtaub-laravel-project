<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\Request;

trait ApiHelpers
{
    // ?per_page=20 (default 15 unless the live page used another limit, max 100)
    protected function perPage(Request $request, int $default = 15): int
    {
        return max(1, min(100, (int) $request->query('per_page', $default)));
    }

    // Routes under /api/v1/admin are named "admin.*"
    protected function isAdminRoute(Request $request): bool
    {
        return $request->routeIs('admin.*');
    }

    // Safe pattern for LIKE searches
    protected function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }
}
