<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Enums\SupportTicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MembershipApplication;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin() || $user->isSubAdmin()) {
            $applications = MembershipApplication::query();
            $events = Event::query()
                ->where('status', EventStatus::Published)
                ->where('ends_at', '>=', now());
            $chapterIds = null;

            if ($user->isSubAdmin()) {
                $chapterIds = $user->administeredChapters()->pluck('chapters.id');
                $applications->whereHas(
                    'chapter.adminUsers',
                    fn ($admins) => $admins->whereKey($user->id),
                );
                $events->whereHas(
                    'chapter.adminUsers',
                    fn ($admins) => $admins->whereKey($user->id),
                );
            }

            $activeMembers = User::query()
                ->where('role', UserRole::Member)
                ->whereHas('memberships', fn ($memberships) => $memberships
                    ->where('status', MembershipStatus::Active)
                    ->where('ends_at', '>', now())
                    ->when(
                        $chapterIds !== null,
                        fn ($memberships) => $memberships->whereIn('chapter_id', $chapterIds),
                    ));
            $registrations = EventRegistration::query()
                ->where('status', EventRegistrationStatus::Registered)
                ->whereHas('event', fn ($events) => $events
                    ->where('status', EventStatus::Published)
                    ->where('ends_at', '>=', now())
                    ->when(
                        $chapterIds !== null,
                        fn ($events) => $events->whereIn('chapter_id', $chapterIds),
                    ));
            $supportTickets = SupportTicket::query()
                ->whereIn('status', [
                    SupportTicketStatus::Open,
                    SupportTicketStatus::InProgress,
                    SupportTicketStatus::Escalated,
                ])
                ->when(
                    $chapterIds !== null,
                    fn ($tickets) => $tickets->whereIn('chapter_id', $chapterIds),
                );

            $counts = $applications
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return response()->json([
                'data' => [
                    'role' => $user->role->value,
                    'applications' => [
                        'submitted' => (int) ($counts[MembershipApplicationStatus::Submitted->value] ?? 0),
                        'under_review' => (int) ($counts[MembershipApplicationStatus::UnderReview->value] ?? 0),
                        'approved' => (int) ($counts[MembershipApplicationStatus::Approved->value] ?? 0),
                        'rejected' => (int) ($counts[MembershipApplicationStatus::Rejected->value] ?? 0),
                    ],
                    'active_members' => $activeMembers->count(),
                    'upcoming_events' => $events->count(),
                    'upcoming_event_registrations' => $registrations->count(),
                    'open_support_tickets' => $supportTickets->count(),
                    'new_inquiries' => $user->isSuperAdmin()
                        ? ContactInquiry::query()->where('status', 'new')->count()
                        : null,
                ],
            ]);
        }

        $applications = $user->membershipApplications()
            ->with(['chapter', 'plan', 'membership'])
            ->latest()
            ->limit(5)
            ->get();
        $memberships = $user->memberships()
            ->with(['chapter', 'plan'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'role' => $user->role->value,
                'applications' => $applications->map(fn ($application) => [
                    'id' => $application->id,
                    'chapter' => $application->chapter->name,
                    'plan' => $application->plan->name,
                    'status' => $application->status->value,
                    'created_at' => $application->created_at?->toISOString(),
                ]),
                'memberships' => $memberships->map(fn ($membership) => [
                    'id' => $membership->id,
                    'chapter' => $membership->chapter->name,
                    'plan' => $membership->plan->name,
                    'status' => $membership->status->value,
                    'starts_at' => $membership->starts_at?->toISOString(),
                    'ends_at' => $membership->ends_at?->toISOString(),
                ]),
                'upcoming_events' => Event::query()
                    ->where('status', EventStatus::Published)
                    ->where('ends_at', '>=', now())
                    ->orderBy('starts_at')
                    ->limit(5)
                    ->get(['id', 'title', 'venue', 'starts_at', 'ends_at'])
                    ->map(fn ($event) => [
                        'id' => $event->id,
                        'title' => $event->title,
                        'venue' => $event->venue,
                        'starts_at' => $event->starts_at?->toISOString(),
                        'ends_at' => $event->ends_at?->toISOString(),
                    ]),
                'profile_complete' => $user->profile !== null
                    && filled($user->profile->business_name)
                    && filled($user->profile->industry),
                'can_apply_for_membership' => in_array($user->role, [UserRole::Guest, UserRole::Member], true),
                'open_support_tickets' => SupportTicket::query()
                    ->where('requester_id', $user->id)
                    ->whereIn('status', [
                        SupportTicketStatus::Open,
                        SupportTicketStatus::InProgress,
                        SupportTicketStatus::Escalated,
                    ])
                    ->count(),
            ],
        ]);
    }
}
