<?php

namespace App\Http\Controllers\Api;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReplySupportTicketRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SupportTicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SupportTicketResource::collection(
            SupportTicket::query()
                ->where('requester_id', $request->user()->id)
                ->with(['requester', 'chapter', 'messages.user'])
                ->orderByDesc('updated_at')
                ->paginate(15),
        );
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $ticket = DB::transaction(function () use ($request): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'requester_id' => $request->user()->id,
                'chapter_id' => $request->validated('chapter_id'),
                'subject' => $request->validated('subject'),
                'category' => $request->validated('category'),
                'priority' => SupportTicketPriority::Normal,
                'status' => SupportTicketStatus::Open,
            ]);
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $request->validated('message'),
                'is_internal' => false,
            ]);

            return $ticket->load(['requester', 'chapter', 'messages.user']);
        });

        return (new SupportTicketResource($ticket))->response()->setStatusCode(201);
    }

    public function show(SupportTicket $supportTicket): SupportTicketResource
    {
        Gate::authorize('view', $supportTicket);

        return new SupportTicketResource(
            $supportTicket->load(['requester', 'chapter', 'messages.user']),
        );
    }

    public function reply(
        ReplySupportTicketRequest $request,
        SupportTicket $supportTicket,
    ): SupportTicketResource {
        Gate::authorize('reply', $supportTicket);
        if (in_array($supportTicket->status, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true)) {
            throw ValidationException::withMessages([
                'message' => ['This support ticket is closed for replies.'],
            ]);
        }

        $isAdminReply = Gate::allows('manage', $supportTicket);
        $supportTicket->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('message'),
            'is_internal' => $isAdminReply && $request->boolean('is_internal'),
        ]);

        return new SupportTicketResource(
            $supportTicket->refresh()->load(['requester', 'chapter', 'messages.user']),
        );
    }
}
