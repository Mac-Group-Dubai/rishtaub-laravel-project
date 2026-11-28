<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileViewRequest;
use App\Http\Resources\ProfileViewerResource;
use App\Models\ProfileView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileViewController extends Controller
{
    use ApiHelpers;

    // POST /api/v1/profile-views   body: { "viewed_id": "<profile id>" }
    // Like the live logProfileView(): looking at your own profile is not logged
    public function store(ProfileViewRequest $request): JsonResponse
    {
        $viewedId = $request->validated()['viewed_id'];

        if ($viewedId === $request->user()->id) {
            return response()->json(null, 204);
        }

        $view = new ProfileView();
        $view->fill([
            'viewer_id' => $request->user()->id,
            'viewed_id' => $viewedId,
            'viewed_at' => now(),
        ])->save();

        return response()->json(['message' => 'View logged.'], 201);
    }

    // GET /api/v1/profile-views  (who viewed me, newest first, live shows the last 50)
    public function index(Request $request): AnonymousResourceCollection
    {
        $page = ProfileView::query()
            ->where('viewed_id', $request->user()->id)
            ->whereHas('viewer', fn ($q) => $q->where('is_active', true))
            ->with('viewer')
            ->orderByDesc('viewed_at')
            ->paginate($this->perPage($request, 50));

        return ProfileViewerResource::collection($page);
    }
}
