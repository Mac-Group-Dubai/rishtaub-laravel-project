<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Api\V1\Concerns\FiltersProfiles;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminProfileUpdateRequest;
use App\Http\Requests\ProfileSearchRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminProfileController extends Controller
{
    use ApiHelpers, FiltersProfiles;

    // GET /api/v1/admin/profiles  (every member, also blocked ones; same filters plus is_active and plan)
    public function index(ProfileSearchRequest $request): AnonymousResourceCollection
    {
        $query = $this->applyProfileFilters(Profile::query(), $request)->orderByDesc('created_at');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($plan = $request->query('plan')) {
            $query->where('plan', (string) $plan);
        }

        return ProfileResource::collection($query->paginate($this->perPage($request, 20)));
    }

    // GET /api/v1/admin/profiles/{id}
    public function show(string $profile): ProfileResource
    {
        return new ProfileResource(Profile::findOrFail($profile));
    }

    // PUT/PATCH /api/v1/admin/profiles/{id}   (edit, block with is_active=false, change plan)
    public function update(AdminProfileUpdateRequest $request, string $profile): ProfileResource
    {
        $model = Profile::findOrFail($profile);
        $old = $model->toArray();

        $model->fill($request->validated())->save();
        $model->refresh();

        // A blocked member is signed out everywhere, like the live site does
        if ($model->is_active === false) {
            User::find($model->id)?->tokens()->delete();
        }

        Audit::log($request, 'profile.update', 'profiles', $model->id, $old, $model->toArray());

        return new ProfileResource($model);
    }
}
