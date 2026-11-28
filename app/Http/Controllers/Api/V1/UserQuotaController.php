<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserQuotaResource;
use App\Models\UserQuota;
use Illuminate\Http\Request;

class UserQuotaController extends Controller
{
    // GET /api/v1/user-quota  (my own quota; members can read it but not change it)
    public function show(Request $request): UserQuotaResource
    {
        return new UserQuotaResource(UserQuota::findOrFail($request->user()->id));
    }
}
