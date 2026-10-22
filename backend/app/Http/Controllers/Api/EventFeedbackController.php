<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventFeedbackRequest;
use App\Http\Resources\EventFeedbackResource;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventFeedbackController extends Controller
{
    public function store(
        StoreEventFeedbackRequest $request,
        Event $event,
    ): JsonResponse {
        $feedback = DB::transaction(function () use ($request, $event): EventFeedback {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($event->status !== EventStatus::Published || $event->ends_at->isFuture()) {
                throw ValidationException::withMessages([
                    'event' => ['Feedback is available after a published event has ended.'],
                ]);
            }

            $registration = EventRegistration::query()
                ->where('event_id', $event->id)
                ->where('user_id', $request->user()->id)
                ->whereNotNull('attended_at')
                ->first();

            if ($registration === null) {
                throw ValidationException::withMessages([
                    'event' => ['Feedback is available only to attendees with a verified account.'],
                ]);
            }

            if ($registration->feedback()->exists()) {
                throw ValidationException::withMessages([
                    'event' => ['Feedback has already been submitted for this event.'],
                ]);
            }

            return $registration->feedback()->create([
                ...$request->validated(),
                'user_id' => $request->user()->id,
                'submitted_at' => now(),
            ]);
        });

        return (new EventFeedbackResource($feedback))
            ->response()
            ->setStatusCode(201);
    }
}
