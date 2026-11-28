<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserQuotaRequest;
use App\Http\Resources\UserQuotaResource;
use App\Models\UserQuota;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserQuotaController extends Controller
{
    use ApiHelpers;

    // GET /api/v1/admin/user-quota?user_id=
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UserQuota::query()->orderByDesc('updated_at');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', (string) $userId);
        }

        return UserQuotaResource::collection($query->paginate($this->perPage($request, 20)));
    }

    // GET /api/v1/admin/user-quota/{user_id}
    public function show(string $user): UserQuotaResource
    {
        return new UserQuotaResource(UserQuota::findOrFail($user));
    }

    // PUT/PATCH /api/v1/admin/user-quota/{user_id}
    public function update(UserQuotaRequest $request, string $user): UserQuotaResource
    {
        $model = UserQuota::findOrFail($user);
        $old = $model->toArray();

        $model->fill($request->validated())->save();
        $model->refresh();

        Audit::log($request, 'user_quota.update', 'user_quota', $model->user_id, $old, $model->toArray());

        return new UserQuotaResource($model);
    }
}
