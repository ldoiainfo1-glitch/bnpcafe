<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Initial API endpoints

The browser client runs at `http://localhost:5173` by default and uses Laravel Sanctum's stateful session authentication. Requests that need a session must send credentials and a configured `Origin`; write requests also use Laravel's CSRF cookie/token flow.

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| `POST` | `/api/auth/login` | Public, rate-limited | Sign in with email and password |
| `POST` | `/api/auth/logout` | Authenticated session | Invalidate the current session |
| `PATCH` | `/api/auth/password` | Authenticated | Set a password after verified email sign-in or change it with the current password |
| `GET` | `/api/user` | Authenticated | Return the current user's safe profile, including whether a password has been set |
| `GET` | `/api/chapters` | Public | List active chapters using a public-safe subset of chapter details |
| `GET` | `/api/admin/chapters` | Super Admin or Sub Admin | List all chapters or only assigned chapters |
| `GET` | `/api/admin/chapters/{chapter}` | Super Admin or assigned Sub Admin | Read a chapter; inaccessible chapters return `404` |
| `POST` | `/api/admin/chapters` | Super Admin | Create a chapter |
| `PATCH` | `/api/admin/chapters/{chapter}` | Super Admin | Update chapter details or deactivate it |
| `GET` | `/api/admin/sub-admins` | Super Admin | List Sub Admins and their assigned chapters |
| `POST` | `/api/admin/sub-admins` | Super Admin | Create a Sub Admin and assign active chapters |
| `PATCH` | `/api/admin/sub-admins/{subAdmin}/chapters` | Super Admin | Replace a Sub Admin's chapter assignments |
| `GET` | `/api/membership-plans` | Public | List active plans in display order |
| `GET` | `/api/admin/membership-plans` | Super Admin | List all plans, including inactive plans |
| `POST` | `/api/admin/membership-plans` | Super Admin | Create a plan |
| `GET` | `/api/admin/membership-plans/{membershipPlan}` | Super Admin | Read a plan |
| `PATCH` | `/api/admin/membership-plans/{membershipPlan}` | Super Admin | Update or deactivate a plan |
| `GET` | `/api/membership-applications` | Authenticated Guest or Member | List the current user's applications |
| `POST` | `/api/membership-applications` | Authenticated Guest or Member | Apply for an active plan in an active chapter |
| `GET` | `/api/memberships` | Authenticated user | List the current user's membership records |
| `GET` | `/api/dashboard` | Authenticated | Role-appropriate dashboard summary |
| `GET` | `/api/profile` | Authenticated | Read the current user's private business profile |
| `PATCH` | `/api/profile` | Authenticated Guest or Member | Update private profile and explicit directory preferences |
| `GET` | `/api/directory` | Active Member, Super Admin, or Sub Admin | Browse opted-in active members; Sub Admins are chapter-scoped |
| `POST` | `/api/memberships/{membership}/payments/razorpay/order` | Membership owner | Create or reuse a server-priced Razorpay order |
| `POST` | `/api/memberships/{membership}/payments/razorpay/confirm` | Membership owner | Verify a Razorpay signature and captured payment |
| `GET` | `/api/payments` | Authenticated user | List the current user's payment records |
| `GET` | `/api/invoices` | Authenticated user | List the current user's issued invoices |
| `GET` | `/api/invoices/{invoice}` | Invoice owner | Read an invoice |
| `POST` | `/api/payments/razorpay/webhook` | Razorpay, signed | Process `payment.captured` notifications idempotently |
| `GET` | `/api/admin/payments` | Super Admin | Review payment records and provider reconciliation IDs |
| `GET` | `/api/admin/invoices` | Super Admin | Review all issued invoices |
| `GET` | `/api/support/tickets` | Authenticated Guest or Member | List the current user's tickets |
| `POST` | `/api/support/tickets` | Authenticated Guest or Member | Open a support ticket with an initial message |
| `GET` | `/api/support/tickets/{supportTicket}` | Requester or authorized admin | Read a ticket and visible messages |
| `POST` | `/api/support/tickets/{supportTicket}/messages` | Requester or authorized admin | Reply or add an authorized internal note |
| `GET` | `/api/admin/support/tickets` | Super Admin or assigned Sub Admin | Review tickets; Sub Admins see assigned chapters only |
| `PATCH` | `/api/admin/support/tickets/{supportTicket}` | Super Admin or assigned Sub Admin | Update priority, status, resolve, close, or escalate a ticket |
| `GET` | `/api/content/pages` | Public | List published content pages |
| `GET` | `/api/content/pages/{slug}` | Public | Read a published content page |
| `GET` | `/api/settings` | Public | Read allowlisted public platform settings |
| `GET` | `/api/admin/content/pages` | Super Admin | List all content pages, including drafts |
| `POST` | `/api/admin/content/pages` | Super Admin | Create a draft or published content page |
| `GET` | `/api/admin/content/pages/{contentPage}` | Super Admin | Read an editable page |
| `PATCH` | `/api/admin/content/pages/{contentPage}` | Super Admin | Update or publish/unpublish a page |
| `GET` | `/api/admin/settings` | Super Admin | List configurable public settings |
| `PATCH` | `/api/admin/settings/{key}` | Super Admin | Update an allowlisted public setting |
| `GET` | `/api/admin/reports/overview` | Super Admin or Sub Admin | Read operational counts for a bounded date range |
| `GET` | `/api/admin/membership-applications` | Super Admin or assigned Sub Admin | Review the application queue, scoped to assigned chapters |
| `GET` | `/api/admin/membership-applications/{membershipApplication}` | Super Admin or assigned Sub Admin | Read an application within the reviewer's scope |
| `PATCH` | `/api/admin/membership-applications/{membershipApplication}` | Super Admin or assigned Sub Admin | Move a submitted application into review, approve it, or reject it |
| `GET` | `/api/events` | Public | List published upcoming events |
| `POST` | `/api/events/{event}/registrations` | Public, rate-limited | Register a guest for an open event |
| `POST` | `/api/events/{event}/feedback` | Authenticated Guest or Member attendee | Submit one rating and optional comment after attendance |
| `POST` | `/api/contact-inquiries` | Public, rate-limited | Submit a contact inquiry |
| `GET` | `/api/admin/events` | Super Admin or assigned Sub Admin | List events; Sub Admins see assigned chapter events only |
| `POST` | `/api/admin/events` | Super Admin | Create an event, initially a draft unless published explicitly |
| `PATCH` | `/api/admin/events/{event}` | Super Admin | Update or cancel an event |
| `GET` | `/api/admin/events/{event}/registrations` | Super Admin or assigned Sub Admin | View registrations for an event within the reviewer's scope |
| `PATCH` | `/api/admin/events/{event}/registrations/{eventRegistration}/attendance` | Super Admin or assigned Sub Admin | Record attendance for a started event |
| `GET` | `/api/admin/events/{event}/feedback` | Super Admin or assigned Sub Admin | View feedback within the reviewer's event scope |
| `GET` | `/api/admin/contact-inquiries` | Super Admin | Review submitted contact inquiries |
| `PATCH` | `/api/admin/contact-inquiries/{contactInquiry}` | Super Admin | Mark an inquiry in progress or resolved |
| `POST` | `/api/guest/access` | Public, rate-limited | Request an email sign-in code; always returns the same `202` response shape |
| `POST` | `/api/guest/access/verify` | Public, rate-limited | Verify the code and establish a browser session |

Guest access start accepts `name`, `email` and optional `phone`. Email is normalized to lowercase; phone formatting separators are removed. The six-digit code expires after ten minutes, is stored only as a password hash, and is limited to five verification attempts. Successful verification creates a Guest only if no account already owns that email; existing roles and profile names are preserved.

An authenticated account without a recorded password can set one after email verification. Passwords must be at least 12 characters and use confirmation; changing an established password also requires the current password. Successful password login records the password as established for legacy accounts.

Sub Admin accounts are created by a Super Admin, cannot self-assign roles, and can be assigned only active chapters. A new Sub Admin can authenticate through the email-code flow and set a password; Super Admins can later replace the chapter assignment list.

The dashboard returns the current user's recent applications and memberships, profile completion, and open support-ticket count. Admin dashboards also include application-status counts, active members, upcoming events and registrations, and open support tickets; Sub Admin metrics are limited to assigned chapters, while contact inquiry counts are shown only to Super Admins.

For safety, a supplied phone number can detect an identity conflict but cannot authenticate an account. A phone identity linked to a different or unknown email suppresses code issuance without changing the generic response. Phone-based account claims require an SMS verification provider and are not enabled yet. New phone identities are stored unverified and must not be used as proof of account ownership.

Verification challenges are pruned hourly and expired memberships are reconciled hourly by Laravel's scheduler. Run `php artisan schedule:work` during local development to exercise scheduled jobs; configure the Laravel scheduler in the production deployment as well. Expiration downgrades Members with no other active membership to Guest.

Membership plans have a name, description, price/currency, duration in months, feature list, active status, and display order. The public endpoint intentionally excludes inactive plans. Plans are deactivated rather than deleted so later membership applications and financial records can retain stable references.

Membership applications are submitted by authenticated Guests or Members. The API uses the signed-in account as the applicant and does not accept a client-supplied user ID. A user may have only one submitted or under-review application at a time; a rejected application may be followed by a new application. Super Admins can review all applications, while Sub Admins can review only applications for chapters assigned to them. Approving an application creates a `pending_payment` membership; payment confirmation and activation are handled by the payments workflow, so approval alone does not grant Member access.

Public event registration is available only for published, upcoming events before registration closes and while capacity remains. Each normalized email can register once per event. Public event and contact submissions are associated with an account only when the requester is authenticated and the submitted email matches that user's verified email identity; supplying another person's email does not claim or expose their account. Event and contact submissions are rate-limited. Public contact inquiries are visible only to Super Admins; event registration details and feedback are visible to Super Admins and Sub Admins assigned to that event's chapter. Feedback requires a verified account linked to an attended registration and can be submitted once after the event ends.

Directory listings include only active Members who opt in to `show_in_directory`. Private profile phone, address, and account email are never returned in directory responses; only the separate `directory_email` and `directory_phone` consent fields are shown.

Support tickets are private to their requester and authorized administrators. Sub Admins can view and escalate tickets only for assigned chapters; unscoped tickets are Super Admin-only. Internal staff notes are never returned to Guests or Members, and resolved/closed tickets cannot be reopened through these endpoints.

CMS page bodies are stored as text and should be rendered as text by clients, not injected as HTML. Public settings are restricted to the allowlisted site name, support email/phone, and home announcement keys; payment or mail secrets are never stored as platform settings. Reports accept an optional `from`/`to` date range (maximum 366 days). Sub Admin reports are chapter-scoped and omit revenue and contact inquiry totals.

Paid membership approval creates a `pending_payment` membership. Razorpay orders are created from the server-side plan price (INR only); the browser cannot provide an amount. Configure `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, and `RAZORPAY_WEBHOOK_SECRET` in the backend environment and register `/api/payments/razorpay/webhook` for the `payment.captured` event. Do not send provider credentials in chat or commit them. The client confirmation signature is checked and the payment is independently fetched from Razorpay; the signed webhook is also checked. A membership becomes active and an invoice record is issued only after a matching captured payment, and repeated confirmations/webhooks are idempotent. Free plans activate on approval without a gateway. The API does not collect or store card data. Invoice endpoints currently return structured invoice data; tax calculations and downloadable PDF rendering are not included.

Configure a real SMTP mail transport in the backend environment before trying the email-code flow interactively. The starter `.env.example` uses Laravel's `log` mailer for local scaffolding; that mailer writes message contents, including sign-in codes, to application logs and must not be used in production.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
