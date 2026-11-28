<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesTargetList;
use App\Http\Controllers\Controller;
use App\Models\Shortlist;

// GET /api/v1/shortlists, POST /api/v1/shortlists, DELETE /api/v1/shortlists/{profile_id}
class ShortlistController extends Controller
{
    use ManagesTargetList;

    protected function listModel(): string
    {
        return Shortlist::class;
    }

    protected function listName(): string
    {
        return 'shortlist';
    }
}
