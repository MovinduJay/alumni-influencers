# Alumni Influencers Platform

Alumni Influencers Platform is a CodeIgniter 3 web application for alumni profile management, Alumni of the Day bidding, API-client management, and university analytics.

The system has two main user types:

- Alumni users manage their profile, qualifications, work history, bids, sponsorships, and events.
- Admin users manage analytics, API clients, API usage, and featured-alumni workflows.

External clients can access selected public data through bearer-token protected API endpoints.

## Main Features

- Alumni registration, login, email verification, and password reset
- Alumni profile management with degrees, certifications, licences, courses, employment, and profile image upload
- Blind bidding workflow for Alumni of the Day placement
- Admin API-client creation, revocation, usage logs, and usage statistics
- Public REST API secured with bearer tokens and scopes
- API documentation page with OpenAPI JSON
- University analytics dashboard with filters, charts, CSV export, and PDF export
- CLI route for scheduled featured-alumni winner selection

## Technology Stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.x |
| Framework | CodeIgniter 3 |
| Database | MySQL |
| Web server | Apache / XAMPP |
| Frontend | Server-rendered PHP views, CSS, JavaScript |
| Charts | Chart.js |
| API docs | OpenAPI / Swagger UI |
| Email | CodeIgniter Email Library with SMTP |

## Project Structure

```text
alumni-influencers/
|-- application/
|   |-- config/
|   |-- controllers/
|   |-- core/
|   |-- helpers/
|   |-- hooks/
|   |-- libraries/
|   |-- models/
|   `-- views/
|-- assets/
|-- docs/
|-- sql/
|   `-- schema.sql
|-- system/
|-- tools/
|-- uploads/
|-- vendor/
|-- .env.example
|-- composer.json
|-- index.php
`-- README.md
```

## Requirements

- PHP 8.x
- MySQL 8.x
- Apache with `mod_rewrite` enabled
- Composer dependencies installed
- XAMPP or an equivalent local PHP/MySQL environment

## Local Setup

1. Copy the environment file.

```bash
cp .env.example .env
```

2. Update `.env` with your local database, base URL, email, and security settings.

For a XAMPP folder like `htdocs/alumni-influencers`, the base URL is usually:

```env
BASE_URL=http://localhost/alumni-influencers/
```

3. Create the database tables.

```bash
mysql -u root -p < sql/schema.sql
```

4. Make sure Apache rewrite is enabled and the project is accessible through the browser.

```text
http://localhost/alumni-influencers/
```

There is no seed file in this version. Add users and API clients through the application, database scripts, or your own test data as required.

## Environment Variables

The project reads runtime configuration from `.env`.

| Variable | Purpose |
| --- | --- |
| `CI_ENV` | Application environment, for example `development` |
| `BASE_URL` | Public application URL with a trailing slash |
| `DB_HOST` | MySQL host |
| `DB_USER` | MySQL username |
| `DB_PASS` | MySQL password |
| `DB_NAME` | MySQL database name |
| `SMTP_HOST` | SMTP server host |
| `SMTP_PORT` | SMTP server port |
| `SMTP_CRYPTO` | SMTP encryption type |
| `SMTP_USER` | SMTP username |
| `SMTP_PASS` | SMTP password or app password |
| `SMTP_FROM` | Sender email address |
| `SMTP_FROM_NAME` | Sender display name |
| `ENCRYPTION_KEY` | CodeIgniter encryption and session key |
| `UNIVERSITY_DOMAIN` | Email domain allowed for registration |
| `SESSION_TIMEOUT` | Session idle timeout in seconds |
| `RATE_LIMIT` | Request limit for sensitive endpoints |
| `CORS_ALLOWED_ORIGIN` | Allowed API browser origin |
| `DEFAULT_API_SCOPE` | Default scope for newly created API clients |
| `ANALYTICS_DASHBOARD_TOKEN` | Bearer token used by the analytics dashboard |
| `MAX_FEATURES_PER_MONTH` | Monthly Alumni of the Day win limit |
| `UPLOAD_PATH` | Profile image upload folder |

## Database

The database schema is stored in:

- `sql/schema.sql`

The schema includes tables for:

- shared user accounts
- alumni profile subtype records
- profile qualifications and work history
- bids and featured alumni history
- sponsorship and event records
- analytics programmes, sectors, outcomes, skills, and presets
- API clients, API scopes, and API access logs

The schema also inserts the required API scope lookup values so a fresh database can create scoped bearer-token clients immediately.

## Architecture

The application follows the CodeIgniter MVC pattern with a small service layer for shared business rules.

| Layer | Main files | Responsibility |
| --- | --- | --- |
| Controllers | `application/controllers` | Handle routes, validate requests, choose views, and return JSON responses |
| Models | `application/models` | Read and write database records using CodeIgniter query builder |
| Libraries | `application/libraries` | Keep reusable business logic such as authentication, API-client handling, and winner selection |
| Views | `application/views` | Render browser pages for alumni, admins, analytics, and API documentation |
| Assets | `assets/js`, `assets/css` | Run dashboard interactions, API calls, charts, exports, and page styling |
| Database | `sql/schema.sql` | Define normalized tables, keys, indexes, and relationships |

Important flows:

- Browser login uses sessions. Alumni users can access profile and bidding pages, while admin users can access API-client management and analytics.
- Public API access uses bearer tokens. Tokens are generated once, stored as SHA-256 hashes, and checked on each protected API request.
- API scopes restrict each client to the endpoints it needs. For example, the analytics dashboard uses `read:alumni,read:analytics`, while an Alumni of the Day client uses `read:alumni_of_day`.
- The analytics dashboard calls `/api/v1/analytics/*` endpoints, receives chart-ready JSON, and renders interactive Chart.js visualisations.
- Blind bidding stores pending bids for future featured dates. The winner-selection service marks the highest eligible bid as the featured alumnus.

## Security Design

- Passwords are hashed with bcrypt using cost 12.
- Verification, reset, API, and bearer tokens are generated with `random_bytes`.
- Verification and reset tokens are stored as hashes and have expiry times.
- Sessions are regenerated after login and protected by an inactivity timeout.
- CSRF protection is enabled for browser forms.
- API routes are excluded from CSRF and protected with bearer tokens and scopes.
- Security headers, CSP, CORS headers, and frame protection are set in the security header hook.
- Rate limiting is applied to sensitive authentication flows.
- Output is escaped in views using `htmlspecialchars` where user-controlled values are displayed.

## Main Web Routes

| Route | Purpose |
| --- | --- |
| `/` | Main application entry |
| `/auth/register` | Alumni registration |
| `/auth/login` | User login |
| `/auth/logout` | Logout |
| `/auth/forgot-password` | Password reset request |
| `/profile` | Alumni profile management |
| `/bidding` | Alumni bidding area |
| `/admin` | Admin dashboard |
| `/admin/api-clients` | API-client management |
| `/analytics` | University analytics dashboard |
| `/api-docs` | API documentation |
| `/docs/spec` | OpenAPI JSON |

## API Routes

| Route | Purpose |
| --- | --- |
| `/api/v1/auth/register` | API registration |
| `/api/v1/auth/login` | API login |
| `/api/v1/auth/me` | Current API user |
| `/api/v1/me/profile` | Authenticated alumni profile |
| `/api/v1/me/bids` | Authenticated alumni bids |
| `/api/v1/me/sponsorships` | Authenticated alumni sponsorships |
| `/api/v1/me/events` | Authenticated alumni events |
| `/api/v1/featured` | Featured alumni |
| `/api/v1/featured/today` | Alumni of the Day |
| `/api/v1/featured-alumni` | Featured alumni listing |
| `/api/v1/alumni` | Alumni directory API |
| `/api/v1/analytics/options` | Analytics filter options |
| `/api/v1/analytics/overview` | Analytics overview data |
| `/api/v1/analytics/alumni` | Filtered alumni analytics |
| `/api/v1/donations/summary` | Donation summary |

## API Authentication

The public API uses bearer authentication.

```http
Authorization: Bearer <token>
```

API clients are created by an admin from:

```text
/admin/api-clients
```

Use the generated bearer token in Postman or Swagger. API keys are not required.

## API Scopes

The current CW2 scope names are:

| Scope | Used for |
| --- | --- |
| `read:alumni` | Alumni directory and alumni profile read endpoints |
| `read:analytics` | Analytics dashboard API endpoints |
| `read:donations` | Donation summary endpoint |
| `read:alumni_of_day` | Alumni of the Day / featured alumni endpoints |
| `write:alumni` | Alumni write/update endpoints |

Some legacy scopes are still accepted by the backend for compatibility:

| Legacy scope | Current equivalent |
| --- | --- |
| `alumni:read` | `read:alumni` |
| `alumni:write` | `write:alumni` |
| `featured:read` | `read:alumni_of_day` |

If Postman returns `403 Forbidden` with `Insufficient token scope`, create or select an API client that includes the scope needed by that endpoint. For Alumni of the Day, use `read:alumni_of_day`.

The admin API-client form only lists the current CW2 scope names. Legacy scope aliases are supported only so older existing clients continue to work.

## Checking the API in Postman

1. Open Postman and create a new request.
2. Set the method and URL, for example:

```text
GET http://localhost/alumni-influencers/api/v1/featured/today
```

3. Open the Authorization tab.
4. Select `Bearer Token`.
5. Paste the token generated from the admin API-client page.
6. Send the request.

For Alumni of the Day, the selected API client must include:

```text
read:alumni_of_day
```

## API Documentation

- API docs page: `/api-docs`
- OpenAPI JSON: `/docs/spec`

The docs page is intended for checking endpoints during development. Authorize with a bearer token, then expand an endpoint and use the built-in request tester.

## Scheduled Winner Selection

The featured-alumni winner can be selected from the CLI.

```bash
php index.php cron select_winner
php index.php cron select_winner 2026-03-15
```

The first command runs for the current date. The second command runs for a specific date.

## Demo Data Checklist

There is no seed file in this version. Before a deployment demo, make sure the database contains enough real or test records to demonstrate:

- at least one admin account
- several alumni accounts with completed profile sections
- programmes, industry sectors, alumni outcomes, and skills
- bids, sponsorships, event participation, and at least one featured alumnus
- API clients for analytics and Alumni of the Day
- API access logs generated by calling protected endpoints

The analytics dashboard depends on these records. Empty tables will make the charts appear empty even when the code is working.

## Notes

- Registration is restricted by the configured university email domain.
- Uploaded profile images are stored under `uploads/profile_images/`.
- Admin-only pages require a logged-in admin session.
- API endpoints require bearer tokens when protected by scope checks.
- `::1` in logs means the request came from localhost over IPv6.

## License

This project is based on CodeIgniter 3 and includes the framework under its original licensing terms.
