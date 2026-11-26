<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlogAuthorRequest;
use App\Http\Resources\BlogAuthorResource;
use App\Models\BlogAuthor;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogAuthorController extends Controller
{
    use ApiHelpers;

    // GET /api/v1/admin/blog-authors?q=
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = BlogAuthor::query()->orderBy('name');

        if ($q = $request->query('q')) {
            $query->where('name', 'like', $this->like((string) $q));
        }

        return BlogAuthorResource::collection($query->paginate($this->perPage($request)));
    }

    public function show(string $author): BlogAuthorResource
    {
        return new BlogAuthorResource(BlogAuthor::findOrFail($author));
    }

    public function store(BlogAuthorRequest $request): JsonResponse
    {
        $author = new BlogAuthor();
        $author->fill($request->validated());
        $author->save();
        $author->refresh();

        Audit::log($request, 'blog_author.create', 'blog_authors', $author->id, null, $author->toArray());

        return (new BlogAuthorResource($author))->response()->setStatusCode(201);
    }

    public function update(BlogAuthorRequest $request, string $author): BlogAuthorResource
    {
        $model = BlogAuthor::findOrFail($author);
        $old = $model->toArray();

        $model->fill($request->validated())->save();

        Audit::log($request, 'blog_author.update', 'blog_authors', $model->id, $old, $model->toArray());

        return new BlogAuthorResource($model);
    }

    public function destroy(Request $request, string $author): JsonResponse
    {
        $model = BlogAuthor::findOrFail($author);

        // blogs.author_id points here, so an author with blogs cannot be removed
        if ($model->blogs()->exists()) {
            return response()->json([
                'message' => 'This author still has blogs. Delete or reassign them first.',
            ], 409);
        }

        $old = $model->toArray();
        $model->delete();

        Audit::log($request, 'blog_author.delete', 'blog_authors', $author, $old, null);

        return response()->json(null, 204);
    }
}
