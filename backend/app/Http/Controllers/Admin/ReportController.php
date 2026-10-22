<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformReportRequest;
use App\Models\ContactInquiry;
use App\Models\EventRegistration;
use App\Models\MembershipApplication;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function overview(PlatformReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $from = Carbon::parse($request->validated('from'))->startOfDay();
        $to = Carbon::parse($request->validated('to'))->endOfDay();
        $chapterIds = $user->isSubAdmin()
            ? $user->administeredChapters()->pluck('chapters.id')
            : null;

        $applications = MembershipApplication::query()
            ->whereBetween('created_at', [$from, $to]);
        $registrations = EventRegistration::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('event', fn ($events) => $events->when(
                $chapterIds !== null,
                fn ($events) => $events->whereIn('chapter_id', $chapterIds),
            ));
        $attendees = EventRegistration::query()
            ->whereBetween('attended_at', [$from, $to])
            ->whereHas('event', fn ($events) => $events->when(
                $chapterIds !== null,
                fn ($events) => $events->whereIn('chapter_id', $chapterIds),
            ));
        $tickets = SupportTicket::query()
            ->whereIn('status', ['open', 'in_progress', 'escalated'])
            ->when(
                $chapterIds !== null,
                fn ($tickets) => $tickets->whereIn('chapter_id', $chapterIds),
            );
        $activeMembers = User::query()
            ->where('role', 'member')
            ->whereHas('memberships', fn ($memberships) => $memberships
                ->where('status', MembershipStatus::Active)
                ->where('ends_at', '>', now()))
            ->when(
                $chapterIds !== null,
                fn ($members) => $members->whereIn('chapter_id', $chapterIds),
            );

        if ($chapterIds !== null) {
            $applications->whereIn('chapter_id', $chapterIds);
        }

        $revenue = $user->isSuperAdmin()
            ? Payment::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$from, $to])
                ->selectRaw('currency, SUM(amount_minor) as amount_minor')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get()
                ->map(fn ($row) => [
                    'currency' => $row->currency,
                    'amount_minor' => (int) $row->amount_minor,
                ])
                : null;

        return response()->json([
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'active_members' => $activeMembers->count(),
                'applications_received' => $applications->count(),
                'event_registrations' => $registrations->count(),
                'attendees' => $attendees->count(),
                'open_support_tickets' => $tickets->count(),
                'new_inquiries' => $user->isSuperAdmin()
                    ? ContactInquiry::query()->whereBetween('created_at', [$from, $to])->count()
                    : null,
                'revenue_by_currency' => $revenue,
            ],
        ]);
    }
}
