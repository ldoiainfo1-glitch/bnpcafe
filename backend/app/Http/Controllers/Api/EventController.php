<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRegistrationRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\VerifiedIdentityMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return EventResource::collection(
            Event::query()
                ->with('chapter')
                ->where('status', EventStatus::Published)
                ->where('ends_at', '>=', now())
                ->orderBy('starts_at')
                ->paginate(20),
        );
    }

    public function register(
        StoreEventRegistrationRequest $request,
        Event $event,
        VerifiedIdentityMatcher $identityMatcher,
    ): JsonResponse {
        $data = $request->validated();

        $registration = DB::transaction(function () use ($request, $event, $data, $identityMatcher): EventRegistration {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);

            if ($event->status !== EventStatus::Published
                || $event->starts_at->isPast()
                || ($event->registration_closes_at !== null && $event->registration_closes_at->isPast())) {
                throw ValidationException::withMessages([
                    'event' => ['Registration for this event is closed.'],
                ]);
            }

            if ($event->registrations()->where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['This email address is already registered for the event.'],
                ]);
            }

            if ($event->capacity !== null
                && $event->registrations()
                    ->where('status', EventRegistrationStatus::Registered)
                    ->count() >= $event->capacity) {
                throw ValidationException::withMessages([
                    'event' => ['This event has reached its registration capacity.'],
                ]);
            }

            return $event->registrations()->create([
                ...$data,
                'status' => EventRegistrationStatus::Registered,
                'user_id' => $identityMatcher->match($request->user(), $data['email'])?->id,
            ]);
        });

        return response()->json([
            'message' => 'Your event registration has been received.',
            'registration_id' => $registration->id,
        ], 201);
    }
}
