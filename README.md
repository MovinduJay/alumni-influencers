# Alumni Influencers Platform

Alumni Influencers Platform is a CodeIgniter 3 application for alumni identity, profile management, blind bidding, and controlled API access.

The project combines a server-rendered web interface with a documented REST API. Alumni users can register, verify email addresses, manage professional profile data, participate in blind bidding for featured placement, and track sponsorships and event participation. Admin users can manage API clients and inspect API usage. External consumers can access public alumni and featured-alumni data through bearer-token protected endpoints.

## Table of Contents

- [Highlights](#highlights)
- [Core Modules](#core-modules)
- [Architecture](#architecture)
- [Stack](#stack)
- [Project Layout](#project-layout)
- [Requirements](#requirements)
- [Setup](#setup)
- [Environment Variables](#environment-variables)
- [Database](#database)
- [Main Routes](#main-routes)
- [Authentication Model](#authentication-model)
- [API Documentation](#api-documentation)
- [Scheduled Winner Selection](#scheduled-winner-selection)
- [Notes](#notes)
- [License](#license)

## Highlights

- CodeIgniter 3 MVC application running on PHP 8.x
- MySQL-backed relational data model
- Session-based browser authentication
- Bearer-token protected public API with scope checks
- Email verification and password reset via SMTP
- Blind bidding workflow for featured alumni placement
- Swagger UI and OpenAPI JSON documentation
- Admin API-client management and access logs

## Core Modules

### Web Modules

- `Auth`
  - registration
  - login and logout
  - email verification
  - forgot-password and reset-password flows
- `Profile`
  - personal profile editing
  - degrees
  - certifications
  - licences
  - courses
  - employment history
  - profile image upload
- `Bidding`
  - blind bid placement and updates
  - sponsorship tracking
  - alumni event participation
  - bid history
  - manual winner selection
- `Admin`
  - API client creation
  - client revocation
  - usage logs
  - usage statistics

### API Modules

- `/api/v1/auth/*`
- `/api/v1/me/*`
- `/api/v1/admin/*`
- `/api/v1/featured`
- `/api/v1/featured/today`
- `/api/v1/featured-alumni/*`
- `/api/v1/alumni/*`

## Architecture

The application uses a layered MVC structure:

- `controllers`
  - request handling and response orchestration
- `models`
  - persistence and database query logic
- `views`
  - server-rendered HTML pages and Swagger UI
- `libraries`
  - reusable business services such as authentication, admin operations, and winner selection
- `hooks`
  - shared request-level concerns such as security headers

Key controllers:

- `Auth`
- `Profile`
- `Bidding`
- `Admin`
- `Api`
- `Docs`
- `Cron`

Key models:

- `Alumni_model`
- `Profile_model`
- `Bid_model`
- `Api_client_model`

Key services:

- `Auth_service`
- `Admin_service`
- `Bid_winner_service`

## Stack

| Layer | Technology | Purpose |
| --- | --- | --- |
| Runtime | PHP 8.x | Server-side execution |
| Framework | CodeIgniter 3 | MVC structure, routing, controllers, views, libraries |
| Database | MySQL | Persistent relational storage |
| Web Server | Apache / XAMPP | Local hosting and URL rewriting |
| Session Auth | CodeIgniter Session Library | Browser authentication |
| Validation | CodeIgniter Form Validation | Request and form validation |
| Email | CodeIgniter Email Library + SMTP | Verification, reset, and notification emails |
| API Auth | Bearer token validation | External API access control |
| Documentation | Swagger UI + OpenAPI 3 | Interactive API reference |
| Background Task | CLI cron route | Winner selection workflow |

## Project Layout

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
|-- sql/
|   |-- schema.sql
|   `-- seed.sql
|-- uploads/
|-- system/
|-- .env
|-- .env.example
|-- .htaccess
|-- composer.json
|-- index.php
`-- README.md
```

## Requirements

- PHP 8.x
- MySQL 8.x
- Apache with `mod_rewrite` enabled, or equivalent local web server support

## Setup

1. Copy the environment template.

```bash
cp .env.example .env
```

2. Update database, base URL, and SMTP values in `.env`.

3. Create the database schema.

```bash
mysql -u root -p < sql/schema.sql
```

4. Optionally load seed data.

```bash
mysql -u root -p < sql/seed.sql
```

5. Point the web server at the project root.

6. Confirm that `.htaccess` rewriting is enabled.

## Environment Variables

The project uses `.env` for runtime configuration.

| Variable | Required | Purpose | Example |
| --- | --- | --- | --- |
| `CI_ENV` | Yes | Application environment | `development` |
| `BASE_URL` | Yes | Public base URL with trailing slash | `http://localhost:8080/` |
| `DB_HOST` | Yes | MySQL host | `localhost` |
| `DB_USER` | Yes | MySQL username | `alumni_user` |
| `DB_PASS` | Yes | MySQL password | `secret` |
| `DB_NAME` | Yes | Database name | `alumni_platform` |
| `SMTP_HOST` | Yes | SMTP server host | `smtp.gmail.com` |
| `SMTP_PORT` | Yes | SMTP server port | `587` |
| `SMTP_CRYPTO` | Yes | SMTP transport security | `tls` |
| `SMTP_USER` | Yes | SMTP username | `example@gmail.com` |
| `SMTP_PASS` | Yes | SMTP password or app password | `app-password` |
| `SMTP_FROM` | Yes | Sender email address | `noreply@westminster.ac.uk` |
| `SMTP_FROM_NAME` | Yes | Sender display name | `Alumni Influencers Platform` |
| `UNIVERSITY_DOMAIN` | Yes | Allowed registration email domain | `westminster.ac.uk` |
| `SESSION_TIMEOUT` | Yes | Session inactivity timeout in seconds | `7200` |
| `LOG_THRESHOLD` | No | CodeIgniter log verbosity | `2` |
| `VERIFICATION_TOKEN_EXPIRY` | Yes | Verification token lifetime in hours | `24` |
| `RESET_TOKEN_EXPIRY` | Yes | Password reset token lifetime in hours | `1` |
| `RATE_LIMIT` | Yes | Sensitive endpoint request cap per minute | `60` |
| `CORS_ALLOWED_ORIGIN` | Yes | Allowed browser origin for API CORS headers | `https://localhost` |
| `DEFAULT_API_SCOPE` | Yes | Default scope for newly created API clients | `featured:read,alumni:read` |
| `MAX_FEATURES_PER_MONTH` | Yes | Monthly featured-placement limit | `3` |
| `MAX_IMAGE_SIZE` | Yes | Max upload size in KB | `2048` |
| `MAX_IMAGE_WIDTH` | Yes | Max upload width in pixels | `4000` |
| `MAX_IMAGE_HEIGHT` | Yes | Max upload height in pixels | `4000` |
| `UPLOAD_PATH` | Yes | Upload storage path | `./uploads/profile_images/` |

Minimum local setup usually requires valid values for database, base URL, and SMTP settings.

## Database

The database source of truth is:

- [`sql/schema.sql`](sql/schema.sql)

Optional seed data:

- [`sql/seed.sql`](sql/seed.sql)

The schema covers:

- alumni accounts
- profile records
- bids
- featured alumni history
- sponsorships
- event participation
- API clients
- API scopes
- API access logs

## Main Routes

### Web

- `/`
- `/auth/register`
- `/auth/login`
- `/auth/logout`
- `/auth/verify/{token}`
- `/auth/forgot-password`
- `/auth/reset-password/{token}`
- `/profile`
- `/bidding`
- `/admin/api-clients`
- `/api-docs`

### API

- `/api/v1/auth/register`
- `/api/v1/auth/verify`
- `/api/v1/auth/forgot-password`
- `/api/v1/auth/reset-password`
- `/api/v1/auth/login`
- `/api/v1/auth/me`
- `/api/v1/auth/logout`
- `/api/v1/me/profile`
- `/api/v1/me/bids`
- `/api/v1/me/sponsorships`
- `/api/v1/me/events`
- `/api/v1/featured`
- `/api/v1/featured/today`
- `/api/v1/featured-alumni`
- `/api/v1/alumni`

## Authentication Model

### Browser Users

- authenticated through CodeIgniter sessions
- protected by session timeout and regeneration rules
- CSRF protection applied to browser form flows

### API Clients

- authenticated through bearer tokens
- tokens validated against stored client records
- scope-based authorization applied to protected resources

## API Documentation

- Swagger UI: `/api-docs`
- OpenAPI JSON: `/docs/spec`

## Scheduled Winner Selection

The project includes a CLI entry point for featured-alumni winner resolution.

```bash
php index.php cron select_winner
php index.php cron select_winner 2026-03-15
```

## Notes

- The application is built for a university alumni use case and restricts registration by configured email domain.
- Uploaded profile images are stored under `uploads/profile_images/`.
- The API supports both self-service session endpoints and external bearer-token endpoints.

## License

The project is based on CodeIgniter and includes the framework under its original licensing terms.
