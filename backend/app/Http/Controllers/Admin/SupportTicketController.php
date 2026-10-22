<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SupportTicketController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SupportTicket::class);

        $query = SupportTicket::query()
            ->with(['requester', 'chapter', 'messages.user'])
            ->orderByDesc('updated_at');

        if (request()->user()->isSubAdmin()) {
            $query->whereHas(
                'chapter.adminUsers',
                fn ($admins) => $admins->whereKey(request()->user()->id),
            );
        }

        return SupportTicketResource::collection($query->paginate(25));
    }

    public function update(
        UpdateSupportTicketRequest $request,
        SupportTicket $supportTicket,
    ): SupportTicketResource {
        Gate::authorize('manage', $supportTicket);

        $ticket = DB::transaction(function () use ($request, $supportTicket): SupportTicket {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($supportTicket->id);
            if (in_array($ticket->status, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Resolved or closed tickets cannot be reopened.'],
                ]);
            }

            $status = $request->validated('status');
            $ticket->update([
                ...$request->validated(),
                'resolved_at' => in_array($status, [
                    SupportTicketStatus::Resolved->value,
                    SupportTicketStatus::Closed->value,
                ], true) ? now() : null,
                'escalated_by' => $status === SupportTicketStatus::Escalated->value
                    ? $request->user()->id
                    : $ticket->escalated_by,
            ]);

            return $ticket->refresh()->load(['requester', 'chapter', 'messages.user']);
        });

        return new SupportTicketResource($ticket);
    }
}
