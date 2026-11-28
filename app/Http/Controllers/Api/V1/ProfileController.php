<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Api\V1\Concerns\FiltersProfiles;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanChangeRequest;
use App\Http\Requests\ProfileSearchRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\PublicProfileResource;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileController extends Controller
{
    use ApiHelpers, FiltersProfiles;

    // GET /api/v1/profiles?gender=&religion=&country=&city=&caste=&marital_status=&profession=&mother_tongue=&age_from=&age_to=&q=
    // Only active members, like the live searchProfiles() (live default limit was 20)
    public function index(ProfileSearchRequest $request): AnonymousResourceCollection
    {
        $query = $this->applyProfileFilters(Profile::query()->where('is_active', true), $request)
            ->orderByDesc('created_at');

        return PublicProfileResource::collection($query->paginate($this->perPage($request, 20)));
    }

    // GET /api/v1/profiles/me
    public function showMe(Request $request): ProfileResource
    {
        return new ProfileResource($this->myProfile($request));
    }

    // PUT/PATCH /api/v1/profiles/me
    public function updateMe(ProfileUpdateRequest $request): ProfileResource
    {
        $profile = $this->myProfile($request);
        $profile->fill($request->validated())->save();

        return new ProfileResource($profile->refresh());
    }

    // PATCH /api/v1/profiles/me/plan   body: { "plan": "free" | "premium" }
    public function changePlan(PlanChangeRequest $request): ProfileResource
    {
        $profile = $this->myProfile($request);
        $profile->fill([
            'plan' => $request->validated()['plan'],
            'plan_activated_at' => now(),
        ])->save();

        return new ProfileResource($profile->refresh());
    }

    // GET /api/v1/profiles/{id}  (active members only, like the live getProfileById())
    public function show(Request $request, string $profile): JsonResource
    {
        $model = Profile::where('is_active', true)->findOrFail($profile);

        // my own profile comes with phone and email, other members' profiles do not
        return $request->user()->id === $model->id
            ? new ProfileResource($model)
            : new PublicProfileResource($model);
    }

    private function myProfile(Request $request): Profile
    {
        return $request->user()->profile ?? abort(404);
    }
}
