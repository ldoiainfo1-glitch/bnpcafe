<?php

use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\ContactInquiryController as AdminContactInquiryController;
use App\Http\Controllers\Admin\ContentPageController as AdminContentPageController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\FinancialController as AdminFinancialController;
use App\Http\Controllers\Admin\MembershipApplicationController as AdminMembershipApplicationController;
use App\Http\Controllers\Admin\MembershipPlanController as AdminMembershipPlanController;
use App\Http\Controllers\Admin\PlatformSettingController as AdminPlatformSettingController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SubAdminController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactInquiryController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventFeedbackController;
use App\Http\Controllers\Api\GuestAccessController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MemberProfileController;
use App\Http\Controllers\Api\MembershipApplicationController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlatformSettingController;
use App\Http\Controllers\Api\PublicChapterController;
use App\Http\Controllers\Api\RazorpayWebhookController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/guest/access', [GuestAccessController::class, 'start'])
    ->middleware('throttle:guest-access-start');
Route::post('/guest/access/verify', [GuestAccessController::class, 'verify'])
    ->middleware('throttle:guest-access-verify');
Route::get('/membership-plans', [MembershipPlanController::class, 'index']);
Route::get('/chapters', [PublicChapterController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::post('/events/{event}/registrations', [EventController::class, 'register'])
    ->middleware('throttle:event-registration')
    ->whereNumber('event');
Route::post('/events/{event}/feedback', [EventFeedbackController::class, 'store'])
    ->middleware('auth:sanctum')
    ->whereNumber('event');
Route::post('/contact-inquiries', [ContactInquiryController::class, 'store'])
    ->middleware('throttle:contact-inquiry');
Route::post('/payments/razorpay/webhook', RazorpayWebhookController::class);
Route::get('/content/pages', [ContentController::class, 'index']);
Route::get('/content/pages/{slug}', [ContentController::class, 'show'])
    ->where('slug', '[a-z0-9-]+');
Route::get('/settings', [PlatformSettingController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });
    Route::patch('/auth/password', [AuthController::class, 'updatePassword']);
    Route::get('/membership-applications', [MembershipApplicationController::class, 'index']);
    Route::post('/membership-applications', [MembershipApplicationController::class, 'store']);
    Route::get('/memberships', [MembershipApplicationController::class, 'memberships']);
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::get('/profile', [MemberProfileController::class, 'show']);
    Route::patch('/profile', [MemberProfileController::class, 'update']);
    Route::get('/directory', [DirectoryController::class, 'index']);
    Route::get('/support/tickets', [SupportTicketController::class, 'index']);
    Route::post('/support/tickets', [SupportTicketController::class, 'store']);
    Route::get('/support/tickets/{supportTicket}', [SupportTicketController::class, 'show'])
        ->whereNumber('supportTicket');
    Route::post('/support/tickets/{supportTicket}/messages', [SupportTicketController::class, 'reply'])
        ->whereNumber('supportTicket');
    Route::post('/memberships/{membership}/payments/razorpay/order', [PaymentController::class, 'createRazorpayOrder'])
        ->whereNumber('membership');
    Route::post('/memberships/{membership}/payments/razorpay/confirm', [PaymentController::class, 'confirmRazorpayPayment'])
        ->whereNumber('membership');
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
        ->whereNumber('invoice');
});

Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->group(function (): void {
        Route::get('/chapters', [ChapterController::class, 'index']);
        Route::post('/chapters', [ChapterController::class, 'store']);
        Route::get('/sub-admins', [SubAdminController::class, 'index']);
        Route::post('/sub-admins', [SubAdminController::class, 'store']);
        Route::patch('/sub-admins/{subAdmin}/chapters', [SubAdminController::class, 'update'])
            ->whereNumber('subAdmin');
        Route::get('/chapters/{chapter}', [ChapterController::class, 'show'])
            ->whereNumber('chapter');
        Route::patch('/chapters/{chapter}', [ChapterController::class, 'update'])
            ->whereNumber('chapter');
        Route::get('/membership-plans', [AdminMembershipPlanController::class, 'index']);
        Route::post('/membership-plans', [AdminMembershipPlanController::class, 'store']);
        Route::get('/membership-plans/{membershipPlan}', [AdminMembershipPlanController::class, 'show'])
            ->whereNumber('membershipPlan');
        Route::patch('/membership-plans/{membershipPlan}', [AdminMembershipPlanController::class, 'update'])
            ->whereNumber('membershipPlan');
        Route::get('/membership-applications', [AdminMembershipApplicationController::class, 'index']);
        Route::get('/membership-applications/{membershipApplication}', [AdminMembershipApplicationController::class, 'show'])
            ->whereNumber('membershipApplication');
        Route::patch('/membership-applications/{membershipApplication}', [AdminMembershipApplicationController::class, 'review'])
            ->whereNumber('membershipApplication');
        Route::get('/events', [AdminEventController::class, 'index']);
        Route::post('/events', [AdminEventController::class, 'store']);
        Route::patch('/events/{event}', [AdminEventController::class, 'update'])
            ->whereNumber('event');
        Route::get('/events/{event}/registrations', [AdminEventController::class, 'registrations'])
            ->whereNumber('event');
        Route::patch('/events/{event}/registrations/{eventRegistration}/attendance', [AdminEventController::class, 'markAttendance'])
            ->whereNumber('event')
            ->whereNumber('eventRegistration');
        Route::get('/events/{event}/feedback', [AdminEventController::class, 'feedback'])
            ->whereNumber('event');
        Route::get('/contact-inquiries', [AdminContactInquiryController::class, 'index']);
        Route::patch('/contact-inquiries/{contactInquiry}', [AdminContactInquiryController::class, 'update'])
            ->whereNumber('contactInquiry');
        Route::get('/payments', [AdminFinancialController::class, 'payments']);
        Route::get('/invoices', [AdminFinancialController::class, 'invoices']);
        Route::get('/support/tickets', [AdminSupportTicketController::class, 'index']);
        Route::patch('/support/tickets/{supportTicket}', [AdminSupportTicketController::class, 'update'])
            ->whereNumber('supportTicket');
        Route::get('/content/pages', [AdminContentPageController::class, 'index']);
        Route::post('/content/pages', [AdminContentPageController::class, 'store']);
        Route::get('/content/pages/{contentPage}', [AdminContentPageController::class, 'show'])
            ->whereNumber('contentPage');
        Route::patch('/content/pages/{contentPage}', [AdminContentPageController::class, 'update'])
            ->whereNumber('contentPage');
        Route::get('/settings', [AdminPlatformSettingController::class, 'index']);
        Route::patch('/settings/{key}', [AdminPlatformSettingController::class, 'update'])
            ->where('key', '[a-z_]+');
        Route::get('/reports/overview', [AdminReportController::class, 'overview']);
    });
