<?php

namespace App\Providers;

use App\Models\MembershipApplication;
use App\Models\User;
use App\Policies\DirectoryPolicy;
use App\Policies\MembershipApplicationPolicy;
use App\Policies\PlatformReportPolicy;
use App\Policies\SubAdminPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('view-member-directory', [DirectoryPolicy::class, 'viewAny']);
        Gate::define('view-platform-reports', [PlatformReportPolicy::class, 'viewAny']);
        Gate::policy(MembershipApplication::class, MembershipApplicationPolicy::class);
        Gate::policy(User::class, SubAdminPolicy::class);

        RateLimiter::for('login', function (Request $request): array {
            $submittedEmail = $request->input('email');
            $email = is_string($submittedEmail) ? Str::lower($submittedEmail) : '';

            return [
                Limit::perMinute(5)->by('login:email-ip:'.$email.'|'.$request->ip()),
                Limit::perMinute(30)->by('login:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('guest-access-start', function (Request $request): array {
            $submittedEmail = $request->input('email');
            $email = is_string($submittedEmail) ? Str::lower(trim($submittedEmail)) : '';

            return [
                Limit::perMinute(5)->by('guest-access:email-ip:'.$email.'|'.$request->ip()),
                Limit::perMinute(30)->by('guest-access:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('guest-access-verify', function (Request $request): array {
            $submittedEmail = $request->input('email');
            $email = is_string($submittedEmail) ? Str::lower(trim($submittedEmail)) : '';

            return [
                Limit::perMinute(10)->by('guest-verify:email-ip:'.$email.'|'.$request->ip()),
                Limit::perMinute(60)->by('guest-verify:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('event-registration', function (Request $request): array {
            $submittedEmail = $request->input('email');
            $email = is_string($submittedEmail) ? Str::lower(trim($submittedEmail)) : '';

            return [
                Limit::perMinute(5)->by('event-registration:email-ip:'.$email.'|'.$request->ip()),
                Limit::perMinute(30)->by('event-registration:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('contact-inquiry', function (Request $request): array {
            $submittedEmail = $request->input('email');
            $email = is_string($submittedEmail) ? Str::lower(trim($submittedEmail)) : '';

            return [
                Limit::perMinutes(5, 3)->by('contact-inquiry:email-ip:'.$email.'|'.$request->ip()),
                Limit::perHour(20)->by('contact-inquiry:ip:'.$request->ip()),
            ];
        });
    }
}
