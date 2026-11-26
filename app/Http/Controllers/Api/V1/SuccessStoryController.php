<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuccessStoryRequest;
use App\Http\Resources\SuccessStoryResource;
use App\Models\SuccessStory;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SuccessStoryController extends Controller
{
    use ApiHelpers;

    // GET /api/v1/success-stories  (public: published and visible)  |  GET /api/v1/admin/success-stories (admin: all)
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SuccessStory::query()->orderBy('sort_order')->orderByDesc('created_at');

        if (! $this->isAdminRoute($request)) {
            $query->where('published', true)->where('visible', true);
        }

        if ($q = $request->query('q')) {
            $like = $this->like((string) $q);
            $query->where(fn ($w) => $w->where('couple', 'like', $like)->orWhere('location', 'like', $like));
        }

        return SuccessStoryResource::collection($query->paginate($this->perPage($request)));
    }

    public function show(Request $request, string $story): SuccessStoryResource
    {
        $query = SuccessStory::query();

        if (! $this->isAdminRoute($request)) {
            $query->where('published', true)->where('visible', true);
        }

        return new SuccessStoryResource($query->findOrFail($story));
    }

    public function store(SuccessStoryRequest $request): JsonResponse
    {
        $story = new SuccessStory();
        $story->fill($request->validated());
        $story->save();
        $story->refresh();

        Audit::log($request, 'success_story.create', 'success_stories', $story->id, null, $story->toArray());

        return (new SuccessStoryResource($story))->response()->setStatusCode(201);
    }

    public function update(SuccessStoryRequest $request, string $story): SuccessStoryResource
    {
        $model = SuccessStory::findOrFail($story);
        $old = $model->toArray();

        $model->fill($request->validated())->save();

        Audit::log($request, 'success_story.update', 'success_stories', $model->id, $old, $model->toArray());

        return new SuccessStoryResource($model);
    }

    public function destroy(Request $request, string $story): JsonResponse
    {
        $model = SuccessStory::findOrFail($story);
        $old = $model->toArray();
        $model->delete();

        Audit::log($request, 'success_story.delete', 'success_stories', $story, $old, null);

        return response()->json(null, 204);
    }
}
