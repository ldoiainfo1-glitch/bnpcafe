<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MarkEventAttendanceRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventFeedbackResource;
use App\Http\Resources\EventRegistrationResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Event::class);

        $query = Event::query()->with('chapter')->orderByDesc('starts_at');
        if (request()->user()->isSubAdmin()) {
            $query->whereHas(
                'chapter.adminUsers',
                fn ($adminUsers) => $adminUsers->whereKey(request()->user()->id),
            );
        }

        return EventResource::collection($query->paginate(25));
    }

    public function store(StoreEventRequest $request): EventResource
    {
        Gate::authorize('create', Event::class);
        $event = Event::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status' => $request->validated('status', EventStatus::Draft),
        ]);

        return new EventResource($event->refresh()->load('chapter'));
    }

    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        Gate::authorize('update', $event);
        $event->update($request->validated());

        return new EventResource($event->refresh()->load('chapter'));
    }

    public function registrations(Event $event): AnonymousResourceCollection
    {
        Gate::authorize('view', $event);

        return EventRegistrationResource::collection(
            $event->registrations()->orderByDesc('created_at')->paginate(25),
        );
    }

    public function markAttendance(
        MarkEventAttendanceRequest $request,
        Event $event,
        EventRegistration $eventRegistration,
    ): EventRegistrationResource {
        $registration = $event->registrations()
            ->whereKey($eventRegistration->id)
            ->firstOrFail();

        if ($event->status !== EventStatus::Published
            || $event->starts_at->isFuture()
            || $registration->status !== EventRegistrationStatus::Registered) {
            throw ValidationException::withMessages([
                'attended' => ['Attendance cannot be changed for this event registration.'],
            ]);
        }

        $registration->update([
            'attended_at' => $request->validated('attended') ? now() : null,
        ]);

        return new EventRegistrationResource($registration->refresh());
    }

    public function feedback(Event $event): AnonymousResourceCollection
    {
        Gate::authorize('view', $event);

        return EventFeedbackResource::collection(
            EventFeedback::query()
                ->whereHas('registration', fn ($registrations) => $registrations->where('event_id', $event->id))
                ->orderByDesc('submitted_at')
                ->paginate(25),
        );
    }
}
