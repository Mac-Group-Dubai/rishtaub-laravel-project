<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactSubmissionRequest;
use App\Http\Requests\ContactSubmissionStatusRequest;
use App\Http\Resources\ContactSubmissionResource;
use App\Models\ContactSubmission;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactSubmissionController extends Controller
{
    use ApiHelpers;

    // POST /api/v1/contact-submissions  (public, same as the live Contact page)
    public function store(ContactSubmissionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $submission = new ContactSubmission();
        $submission->fill([
            'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status' => 'unread',
        ]);
        $submission->save();

        return response()->json([
            'message' => "Message sent! We'll reply within 24 hours.",
            'data' => ['id' => $submission->id],
        ], 201);
    }

    // GET /api/v1/admin/contact-submissions?status=&q=
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ContactSubmission::query()->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->query('q')) {
            $like = $this->like((string) $q);
            $query->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('subject', 'like', $like));
        }

        return ContactSubmissionResource::collection($query->paginate($this->perPage($request)));
    }

    public function show(string $submission): ContactSubmissionResource
    {
        return new ContactSubmissionResource(ContactSubmission::findOrFail($submission));
    }

    // PATCH /api/v1/admin/contact-submissions/{id}   body: { "status": "read" }
    public function update(ContactSubmissionStatusRequest $request, string $submission): ContactSubmissionResource
    {
        $model = ContactSubmission::findOrFail($submission);
        $old = $model->toArray();

        $model->fill($request->validated())->save();

        Audit::log($request, 'contact_submission.update', 'contact_submissions', $model->id, $old, $model->toArray());

        return new ContactSubmissionResource($model);
    }

    public function destroy(Request $request, string $submission): JsonResponse
    {
        $model = ContactSubmission::findOrFail($submission);
        $old = $model->toArray();
        $model->delete();

        Audit::log($request, 'contact_submission.delete', 'contact_submissions', $submission, $old, null);

        return response()->json(null, 204);
    }
}
