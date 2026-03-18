# Analytics Architecture

## Component Separation

- Web client: `application/views/analytics/index.php`, `assets/js/analytics-dashboard.js`, and `assets/css/analytics-dashboard.css`.
- Backend API: `Api::analytics_options`, `Api::analytics_overview`, and `Api::analytics_alumni`.
- Report/export controller: `Analytics::export_csv`, `Analytics::export_pdf`, and preset actions.
- Data access: `Analytics_model`, which owns all dashboard filtering and aggregate queries.
- Database: normalized analytics dimensions and relationship tables in `sql/schema.sql`.

## Entity Relationships

- `users` 1:1 `alumni` for alumni accounts
- `users.user_type = admin` identifies admin accounts
- `alumni` 1:1 `alumni_outcomes`
- `programmes` 1:N `alumni_outcomes`
- `industry_sectors` 1:N `alumni_outcomes`
- `alumni` M:N `skills` through `alumni_skills`
- `users` 1:N `analytics_filter_presets` for admin-owned presets
- `api_clients` M:N `api_scopes` through `api_client_scopes`
- `api_clients` 1:N `api_access_logs`

## Analytics Endpoints

- `GET /api/v1/analytics/options`
  - Scope: `read:analytics`
  - Returns programmes, industry sectors, and skills for filters.
- `GET /api/v1/analytics/overview`
  - Scope: `read:analytics`
  - Query filters: `programme_id`, `industry_sector_id`, `skill_id`, `graduation_from`, `graduation_to`, `keyword`.
  - Returns dashboard summary, 8 chart datasets, insight severity, and filtered alumni rows.
- `GET /api/v1/analytics/alumni`
  - Scope: `read:alumni`
  - Returns filtered alumni table/export rows.

## Dashboard Charts

- Alumni by Programme: bar chart.
- Industry Sector Distribution: doughnut chart.
- Graduation Year Trend: line chart.
- Top Skills in Demand: horizontal bar chart.
- Curriculum Gap Severity: pie chart.
- Professional Development Sources: radar chart.
- Programme to Sector Pathways: stacked bar chart.
- Featured Placement Outcomes: doughnut chart.

## Security Notes

- API keys and bearer tokens are generated with `random_bytes`, stored as SHA-256 hashes, and shown once at creation.
- Dashboard API calls use a scoped analytics client token.
- Web forms retain CodeIgniter CSRF protection.
- API endpoints return HTTP 403 when the bearer token lacks the required scope.
- Sensitive auth endpoints use rate limiting through `Auth_service`.
