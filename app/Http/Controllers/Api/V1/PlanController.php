<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    use ApiHelpers;

    // GET /api/v1/plans  (public: active plans)   |   GET /api/v1/admin/plans  (admin: all)
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Plan::query()->with('features')->orderBy('sort_order');

        if (! $this->isAdminRoute($request)) {
            $query->where('active', true);
        } elseif ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($q = $request->query('q')) {
            $query->where('name', 'like', $this->like((string) $q));
        }

        return PlanResource::collection($query->paginate($this->perPage($request)));
    }

    // GET /api/v1/plans/{plan}
    public function show(Request $request, string $plan): PlanResource
    {
        $query = Plan::query()->with('features');

        if (! $this->isAdminRoute($request)) {
            $query->where('active', true);
        }

        return new PlanResource($query->findOrFail($plan));
    }

    // POST /api/v1/admin/plans
    public function store(PlanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $plan = DB::transaction(function () use ($data) {
            $plan = new Plan();
            $plan->fill(Arr::except($data, ['features']));
            $plan->save();
            $this->syncFeatures($plan, $data['features'] ?? []);

            return $plan;
        });

        $plan->refresh()->load('features');
        Audit::log($request, 'plan.create', 'plans', $plan->id, null, $plan->toArray());

        return (new PlanResource($plan))->response()->setStatusCode(201);
    }

    // PUT/PATCH /api/v1/admin/plans/{plan}
    public function update(PlanRequest $request, string $plan): PlanResource
    {
        $model = Plan::with('features')->findOrFail($plan);
        $old = $model->toArray();
        $data = $request->validated();

        DB::transaction(function () use ($model, $data) {
            $model->fill(Arr::except($data, ['id', 'features']))->save();

            if (array_key_exists('features', $data)) {
                $this->syncFeatures($model, $data['features']);
            }
        });

        $model->refresh()->load('features');
        Audit::log($request, 'plan.update', 'plans', $model->id, $old, $model->toArray());

        return new PlanResource($model);
    }

    // DELETE /api/v1/admin/plans/{plan}
    public function destroy(Request $request, string $plan): JsonResponse
    {
        $model = Plan::with('features')->findOrFail($plan);
        $old = $model->toArray();

        DB::transaction(function () use ($model) {
            $model->features()->delete();
            $model->delete();
        });

        Audit::log($request, 'plan.delete', 'plans', $plan, $old, null);

        return response()->json(null, 204);
    }

    // Replaces all features of a plan, keeping the order that was sent
    private function syncFeatures(Plan $plan, array $features): void
    {
        $plan->features()->delete();

        foreach (array_values($features) as $i => $feature) {
            $plan->features()->create([
                'label' => $feature['label'],
                'included' => $feature['included'] ?? true,
                'sort_order' => $i,
            ]);
        }
    }
}
