<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Http\Requests\TargetProfileRequest;
use App\Http\Resources\PublicProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Shared code for "my list of other profiles" (shortlist and ignored profiles).
 * Controllers using it only say which model they work with.
 */
trait ManagesTargetList
{
    use ApiHelpers;

    abstract protected function listModel(): string;

    abstract protected function listName(): string;

    // GET: the profiles on my list, newest first (inactive profiles are hidden, like the live RLS)
    public function index(Request $request): AnonymousResourceCollection
    {
        $model = $this->listModel();

        $page = $model::query()
            ->where('owner_id', $request->user()->id)
            ->whereHas('target', fn ($q) => $q->where('is_active', true))
            ->with('target')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request, 20));

        $page->setCollection($page->getCollection()->map(fn ($row) => $row->target)->values());

        return PublicProfileResource::collection($page);
    }

    // POST {target_id}
    public function store(TargetProfileRequest $request): JsonResponse
    {
        $model = $this->listModel();
        $ownerId = $request->user()->id;
        $targetId = $request->validated()['target_id'];

        if ($model::where('owner_id', $ownerId)->where('target_id', $targetId)->exists()) {
            return response()->json(['message' => "Already in your {$this->listName()}."], 409);
        }

        $row = new $model();
        $row->fill(['owner_id' => $ownerId, 'target_id' => $targetId])->save();

        return response()->json([
            'message' => "Added to your {$this->listName()}.",
            'data' => ['target_id' => $targetId],
        ], 201);
    }

    // DELETE /{target_profile_id}
    public function destroy(Request $request, string $target): JsonResponse
    {
        $model = $this->listModel();

        $deleted = $model::where('owner_id', $request->user()->id)->where('target_id', $target)->delete();

        abort_if($deleted === 0, 404);

        return response()->json(null, 204);
    }
}
