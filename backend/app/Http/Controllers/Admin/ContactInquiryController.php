<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactInquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactInquiryRequest;
use App\Http\Resources\ContactInquiryResource;
use App\Models\ContactInquiry;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ContactInquiryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ContactInquiry::class);

        return ContactInquiryResource::collection(
            ContactInquiry::query()->orderByDesc('created_at')->paginate(25),
        );
    }

    public function update(
        UpdateContactInquiryRequest $request,
        ContactInquiry $contactInquiry,
    ): ContactInquiryResource {
        Gate::authorize('update', $contactInquiry);

        $inquiry = DB::transaction(function () use ($request, $contactInquiry): ContactInquiry {
            $inquiry = ContactInquiry::query()->lockForUpdate()->findOrFail($contactInquiry->id);
            if ($inquiry->status === ContactInquiryStatus::Resolved) {
                throw ValidationException::withMessages([
                    'status' => ['Resolved inquiries cannot be reopened.'],
                ]);
            }

            $status = ContactInquiryStatus::from($request->validated('status'));
            $inquiry->update([
                'status' => $status,
                'resolved_by' => $status === ContactInquiryStatus::Resolved ? $request->user()->id : null,
                'resolved_at' => $status === ContactInquiryStatus::Resolved ? now() : null,
            ]);

            return $inquiry->refresh();
        });

        return new ContactInquiryResource($inquiry);
    }
}
