<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminRowResource;
use App\Models\IgnoredProfile;
use App\Models\ProfileView;
use App\Models\Shortlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Read-only admin lists of what members did
class AdminActivityController extends Controller
{
    use ApiHelpers;

    // GET /api/v1/admin/profile-views?viewer_id=&viewed_id=
    public function profileViews(Request $request): AnonymousResourceCollection
    {
        $query = ProfileView::query()->orderByDesc('viewed_at');

        foreach (['viewer_id', 'viewed_id'] as $field) {
            if ($value = $request->query($field)) {
                $query->where($field, (string) $value);
            }
        }

        return AdminRowResource::collection($query->paginate($this->perPage($request, 50)));
    }

    // GET /api/v1/admin/shortlists?owner_id=&target_id=
    public function shortlists(Request $request): AnonymousResourceCollection
    {
        return $this->ownerTargetList(Shortlist::query(), $request);
    }

    // GET /api/v1/admin/ignored-profiles?owner_id=&target_id=
    public function ignoredProfiles(Request $request): AnonymousResourceCollection
    {
        return $this->ownerTargetList(IgnoredProfile::query(), $request);
    }

    private function ownerTargetList($query, Request $request): AnonymousResourceCollection
    {
        $query->orderByDesc('created_at');

        foreach (['owner_id', 'target_id'] as $field) {
            if ($value = $request->query($field)) {
                $query->where($field, (string) $value);
            }
        }

        return AdminRowResource::collection($query->paginate($this->perPage($request, 50)));
    }
}
