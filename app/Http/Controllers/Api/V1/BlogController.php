<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlogRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Models\BlogAuthor;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class BlogController extends Controller
{
    use ApiHelpers;

    // GET /api/v1/blogs?category=&q=&page=&per_page=   (public: published only)
    // GET /api/v1/admin/blogs                          (admin: everything)
    public function index(Request $request): AnonymousResourceCollection
    {
        $admin = $this->isAdminRoute($request);
        $query = Blog::query()->with('author');

        if (! $admin) {
            $query->where('published', true)->orderByDesc('published_at');
        } else {
            $query->orderByDesc('created_at');

            if ($request->has('published')) {
                $query->where('published', $request->boolean('published'));
            }
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($q = $request->query('q')) {
            $like = $this->like((string) $q);
            $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
        }

        return BlogResource::collection($query->paginate($this->perPage($request)));
    }

    // GET /api/v1/blogs/{slug-or-id}
    public function show(Request $request, string $blog): BlogResource
    {
        $query = Blog::query()->with('author');

        if (! $this->isAdminRoute($request)) {
            $query->where('published', true);
        }

        $model = $query->where(fn ($w) => $w->where('slug', $blog)->orWhere('id', $blog))->firstOrFail();

        return new BlogResource($model);
    }

    // POST /api/v1/admin/blogs
    public function store(BlogRequest $request): JsonResponse
    {
        $data = $this->withAuthorCopy($request->validated());

        $blog = new Blog();
        $blog->fill($data);
        $blog->save();
        $blog->refresh()->load('author');

        Audit::log($request, 'blog.create', 'blogs', $blog->id, null, Arr::except($blog->toArray(), ['content']));

        return (new BlogResource($blog))->response()->setStatusCode(201);
    }

    // PUT/PATCH /api/v1/admin/blogs/{blog}
    public function update(BlogRequest $request, string $blog): BlogResource
    {
        $model = Blog::findOrFail($blog);
        $old = Arr::except($model->toArray(), ['content']);

        $model->fill($this->withAuthorCopy($request->validated()))->save();
        $model->refresh()->load('author');

        Audit::log($request, 'blog.update', 'blogs', $model->id, $old, Arr::except($model->toArray(), ['content']));

        return new BlogResource($model);
    }

    // DELETE /api/v1/admin/blogs/{blog}
    public function destroy(Request $request, string $blog): JsonResponse
    {
        $model = Blog::findOrFail($blog);
        $old = Arr::except($model->toArray(), ['content']);
        $model->delete();

        Audit::log($request, 'blog.delete', 'blogs', $blog, $old, null);

        return response()->json(null, 204);
    }

    // blogs.author_name and author_avatar are copies of the author row, keep them in sync
    private function withAuthorCopy(array $data): array
    {
        if (! empty($data['author_id'])) {
            $author = BlogAuthor::findOrFail($data['author_id']);
            $data['author_name'] = $author->name;
            $data['author_avatar'] = $author->avatar;
        }

        return $data;
    }
}
