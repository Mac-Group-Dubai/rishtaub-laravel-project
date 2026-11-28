<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesTargetList;
use App\Http\Controllers\Controller;
use App\Models\IgnoredProfile;

// GET /api/v1/ignored-profiles, POST /api/v1/ignored-profiles, DELETE /api/v1/ignored-profiles/{profile_id}
class IgnoredProfileController extends Controller
{
    use ManagesTargetList;

    protected function listModel(): string
    {
        return IgnoredProfile::class;
    }

    protected function listName(): string
    {
        return 'ignored list';
    }
}
