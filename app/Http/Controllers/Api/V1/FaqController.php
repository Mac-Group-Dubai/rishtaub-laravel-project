<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FaqController extends Controller
{
    use ApiHelpers;

    // GET /api/v1/faqs?category=&q=  (public: active only)  |  GET /api/v1/admin/faqs (admin: all)
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Faq::query()->orderBy('sort_order')->orderBy('created_at');

        if (! $this->isAdminRoute($request)) {
            $query->where('active', true);
        } elseif ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($q = $request->query('q')) {
            $like = $this->like((string) $q);
            $query->where(fn ($w) => $w->where('question', 'like', $like)->orWhere('answer', 'like', $like));
        }

        return FaqResource::collection($query->paginate($this->perPage($request)));
    }

    public function show(Request $request, string $faq): FaqResource
    {
        $query = Faq::query();

        if (! $this->isAdminRoute($request)) {
            $query->where('active', true);
        }

        return new FaqResource($query->findOrFail($faq));
    }

    public function store(FaqRequest $request): JsonResponse
    {
        $faq = new Faq();
        $faq->fill($request->validated());
        $faq->save();
        $faq->refresh();

        Audit::log($request, 'faq.create', 'faqs', $faq->id, null, $faq->toArray());

        return (new FaqResource($faq))->response()->setStatusCode(201);
    }

    public function update(FaqRequest $request, string $faq): FaqResource
    {
        $model = Faq::findOrFail($faq);
        $old = $model->toArray();

        $model->fill($request->validated())->save();

        Audit::log($request, 'faq.update', 'faqs', $model->id, $old, $model->toArray());

        return new FaqResource($model);
    }

    public function destroy(Request $request, string $faq): JsonResponse
    {
        $model = Faq::findOrFail($faq);
        $old = $model->toArray();
        $model->delete();

        Audit::log($request, 'faq.delete', 'faqs', $faq, $old, null);

        return response()->json(null, 204);
    }
}
