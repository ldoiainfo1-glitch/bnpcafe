# BNP Cafe web app

React, TypeScript, and Vite client for the Laravel API in `../backend`.

## Local development

1. Start the backend from `../backend` with `php artisan serve`.
2. Copy `.env.example` to `.env`. Leave `VITE_API_URL` blank to use port `8000` on the same hostname as the frontend, or set it to your backend origin.
3. Start the client with `npm install` if needed, then `npm run dev`.
4. Open `http://localhost:5173`.

Laravel must include the frontend origin in both `CORS_ALLOWED_ORIGINS` and `SANCTUM_STATEFUL_DOMAINS`. Keep hostnames consistent (`localhost` for both, or `127.0.0.1` for both) so Sanctum's CSRF cookie is sent correctly. The client uses credentialed sessions and obtains Laravel's CSRF cookie before sign-in, email-code verification, password updates, and sign-out. Do not use API tokens or put provider credentials in the browser.

## Local demo logins

From `backend`, seed four local-only accounts with:

```bash
php artisan db:seed --class=DemoUsersSeeder
```

Use `BnpCafeDemo2026!` for each account:

| Category | Email |
| --- | --- |
| Super Admin | `admin.demo@bnp-cafe.test` |
| Sub Admin | `subadmin.demo@bnp-cafe.test` |
| Member | `member.demo@bnp-cafe.test` |
| Guest | `guest.demo@bnp-cafe.test` |

The Sub Admin is assigned to an active chapter (a demo chapter is created if needed). The seeder refuses to run unless `APP_ENV=local`; these accounts are for local testing only.

## Account creation

There is no public password-signup form. A new Guest enters their details and verifies an email code; the account is created after successful verification. Guests can then submit a membership application. Admin review and payment (or approval of a free plan) determine when a membership becomes active. Sub Admin accounts are provisioned by a Super Admin. Email delivery must be configured for the one-time-code flow.

## Current screens

- Public two-row navigation with phone/email/contact/login utilities and links to Home, Membership, Events, Blog, Testimonials, About Us, and Core Team.
- Public homepage, CMS pages, membership plans, upcoming events, and contact inquiry form, using white page surfaces with navy-blue and gold accents.
- About Us, Blog, Testimonials, and Core Team pages. Published CMS pages with a `blog-` slug prefix appear in the Blog; testimonial and team profiles are not fabricated.
- The homepage business-network hero image is served from `public/homepage-business.jpeg`.
- Event registration form with API-side validation.
- Password sign-in and email one-time-code sign-in.
- Authenticated member dashboard, editable private profile, membership application, opt-in member directory, and support tickets.
- Membership payment, Razorpay checkout confirmation, payment history, and invoice records.
- Role-specific Guest, Member, Sub Admin, and Super Admin dashboards with blue styling, larger type, persistent role-based sidebar navigation, and relevant overview actions.
- Chapter-scoped membership application review, event/attendance management, support ticket triage, operational reports, and published CMS page editing for authorized admins.
- Password setup/change and guest/member forms with server-side validation.

Build with `npm run build`; lint with `npm run lint`.
