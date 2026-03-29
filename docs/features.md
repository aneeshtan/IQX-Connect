# IQX Connect — Feature Guide

Live guides:
- Public marketing guide: `/product`
- In-app documentation: `/documentation`

## Core Concepts
- **Workspaces & Companies**: Multi-company support with one or more workspaces per company; default workspace per company; user assignment per workspace with job title and default workspace.
- **Roles & Access**: Role system (admin, manager, sales, analyst) via `jeremykenedy/laravel-roles`; admin-only admin page; managers inherit workspace-level actions (sources, sync, inline edits); all authenticated users use the one-page CRM.
- **Single-Screen CRM**: Tabbed UI for Leads, Opportunities, Contacts, Customers, Sources, Analytics, Add Lead, Add Opportunity. Pagination, search, filters, and newest-first sorting by default.

## Authentication & Onboarding
- **Email/Password + Google Login**: Social login via Google OAuth (user-side) with auto-creation/upsert of users.
- **First-Run Setup**: New user without a workspace sees “Start a new workspace” flow: create company + workspace in one step and auto-assign as manager/owner.
- **Landing Page**: Marketing homepage at `/` with CTA to register or login and a demo-video placeholder.

## Data Sources & Sync
- **Source Types**: Leads, Opportunities, Reports, Google Ads (placeholder). Source kinds: Google Sheet CSV (public), Google Sheets API (OAuth), Uploaded CSV.
- **User Sources Tab**: View sources, status, last sync, open link, sync, inline edit (managers/admins), add source, sync all.
- **Admin Sources**: Full CRUD plus Google OAuth app save, connect/disconnect company Google account, browse spreadsheets/tabs, create sources from tabs, CSV upload (to leads or opportunities), per-source sync/delete.
- **Sync Engine**: `SheetSourceSyncService` handles downloads, parsing, upserts for leads, opportunities, monthly reports; Google Sheets API path with write-back for lead status/opportunity stage when mapped; CSV imports supported.
- **Error Handling**: Friendly dashboard messages for missing Google OAuth or expired tokens.

## CRM Tabs
- **Leads**: Search, filter by status/source, sort, pagination, inline status change (colored), date column, Google write-back when applicable.
- **Opportunities**: Search, stage filter, sort (date/company/value), inline stage change (colored), date/revenue/timeline.
- **Contacts**: Derived from leads; search/sort; select to open AI-style enrichment panel (signals, recommendations, missing data, readiness).
- **Customers**: Derived from closed-won opportunities; search/sort; select to open enrichment panel (signals, account tier, recommendations, missing post-win data).
- **Manual Add**: Forms for manual leads and opportunities (with optional lead linkage).

## Analytics
- **User Analytics Tab**: KPI cards, SQL performance & deal closures, deal summary, Google Ads performance, ROMI/ROAS, breakdowns, monthly reports, snapshots, benchmark-style sections.
- **Time Windows**: Last month (default), last 30/60/90 days, all time, specific month (with month dropdown). All analytics respect the selected range.
- **Charts & Tables**: SQL vs Closed Won bars, Ads leads vs CPL bars, monthly report table, breakdown progress bars, snapshot cards, efficiency cards.

## Admin
- **Dedicated Admin Page**: Tabs for Analytics, Data Sources, Users & Roles, Companies & Workspaces.
- **Workspace & Company Management**: Create/update/delete workspaces (inline editor), set default, JSON settings, company CRUD, workspace pagination/sorting.
- **User Management**: Create users, assign roles, assign workspaces with job titles, pagination/sorting.
- **Google Config**: Save OAuth client, connect/disconnect Google account, list spreadsheets/tabs, create sources from tabs.

## Reporting & Write-Back
- **Monthly Reports**: Imports report rows (year-month, costs, leads, conversion rates, revenue).
- **Google Sheets Write-Back**: Lead status and opportunity stage updates attempt to sync back when the record originated from a Google Sheets API source.

## Branding & UX
- **IQX Connect Branding**: Custom logo and favicon, updated head tags, auth layout branding, CTA buttons.
- **Design Language**: Gradient backgrounds, rounded cards, tabbed layout, colored statuses, Google Sheets-like simplicity.

## Tests & Tooling
- **Feature Tests**: Dashboard access, first-run workspace creation, sources tab visibility, source edit, Google sync error messaging, contact/customer enrichment selection, registration flow, homepage rendering.
- **Formatting**: `vendor/bin/pint`.
- **Build/Test**: `php artisan test`, `npm run build`.

## Key Paths
- User dashboard component: `resources/views/livewire/crm-dashboard.blade.php`
- User dashboard logic: `app/Livewire/CrmDashboard.php`
- Admin dashboard: `resources/views/livewire/admin-dashboard.blade.php`, `app/Livewire/AdminDashboard.php`
- Google auth (user): `app/Http/Controllers/Auth/GoogleAuthController.php`
- Google Sheets OAuth/admin: `app/Http/Controllers/AdminGoogleOAuthController.php`, `app/Services/GoogleOAuthService.php`, `app/Services/GoogleSheetsService.php`
- Sync engine: `app/Services/SheetSourceSyncService.php`
- Enrichment: `app/Services/WorkspaceEnrichmentService.php`
- Models: `app/Models/Lead.php`, `Opportunity.php`, `SheetSource.php`, `MonthlyReport.php`, `Workspace.php`, `Company.php`

## How to Try Quickly
- `php artisan migrate:fresh --seed`
- Set Google OAuth app (Admin > Data Sources) if you need live Google Sheets API sync/write-back.
- Register or use seeded logins (`admin@iqxconnect.test`, `manager@iqxconnect.test`, `sales@iqxconnect.test`, password `password`).
