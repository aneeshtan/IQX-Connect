# IQX Connect

IQX Connect is a maritime CRM and operations workspace platform built with Laravel, Livewire, Flux, and Tailwind. It is designed for maritime companies that need one system for lead capture, pipeline management, operational execution, reporting, billing, and super admin control across multiple companies and workspaces.

The product supports multiple workspace modes so each maritime business model can use a fit-for-purpose operating setup instead of forcing one generic CRM flow across freight forwarding, container conversion, ship chandling, liner sales, ship management, leasing, and general maritime teams.

## What the Product Covers

IQX Connect combines:

- Commercial CRM: leads, opportunities, contacts, customers, assignments, and lead scoring
- Operational workflows: bookings, shipment jobs, projects, vessel-related workflows, inventory-linked flows, and delivery tracking
- Financial workflows: rates, quotes, costing, invoices, and commercial visibility
- Integrations and migration: Google Sheets, public CSV, uploaded CSV, and CargoWise-style APIs
- Workspace governance: roles, access, exports, settings, notifications, and workspace-specific vocabulary
- Platform administration: super admin overview, billing, workspaces, users, and data source management

## Core Concepts

### Company
A top-level customer account in the platform. A company can own one or more workspaces.

### Workspace
An operating environment for one team or business flow. Each workspace has settings, modules, billing, users, sources, and a selected workspace mode.

### Workspace Mode
A template that defines:

- which modules are enabled
- the default CRM vocabulary
- the lead sources and services available in that business model
- the usage metric used for pricing and capacity tracking

### Operational Record
The benchmark record counted for plan usage. The exact metric changes by workspace mode, for example shipment jobs, projects, bookings, or commercial deals.

### Source
A sync or import connection used to bring data into a workspace. Sources can target leads, opportunities, customers, reports, bookings, shipments, and more depending on the workspace.

## Feature Directory

### Commercial CRM

- Leads: capture and qualify inbound demand
- Opportunities: progress qualified demand through pipeline stages
- Contacts: track customer-side people and linked history
- Customers: manage won accounts and account context
- Assignments: route records to owners and teams
- Lead scoring: prioritize records using commercial signals

### Freight and Finance Workflows

- Rates: maintain buy/sell pricing, validity, and lane context
- Quotes: prepare commercial offers and revisions
- Shipments: track active shipment jobs
- Carriers: manage carrier and service relationships
- Bookings: convert accepted demand into operational bookings
- Job costing: compare buy, sell, and margin
- Invoices: handle AR/AP billing flows linked to operational work

### Project and Delivery Workflows

- Projects: manage awarded project work
- Drawings: manage revisions and technical review steps
- Delivery tracking: monitor delivery and installation readiness
- Delivery tasks: track operational execution tasks

### Maritime-Specific Operating Modules

- Vessel calls: port-call and vessel-linked supply workflows
- Supply orders: requisitions and chandling order handling
- Sailings: liner schedule support
- Customer accounts: account structures for liner workflows
- Fleet: owner and vessel relationship management
- Technical management: commercial and technical handover support
- Crewing: crewing-related management workflows
- Inventory: stock-led container or asset handling
- Leasing: lease and asset deal progression
- Depots: depot-linked asset visibility

### Reporting and Controls

- Flexible reporting windows
- Benchmark-style KPIs
- Customer segmentation
- Collaboration entries and record activity
- Roles and access control
- Workspace exports
- Workspace-level notifications and settings

### Super Admin Console

- Platform overview with users, workspaces, subscriptions, and growth
- Billing directory and workspace plan controls
- Full user master list with search and management
- Full source master list with search and management
- Company and workspace directories
- Safer workspace deletion with typed confirmation

## Workspace Modes

### 1. Freight Forwarder

- Best for: air, ocean, road, customs, and rate-driven forwarding teams
- Usage metric: Shipment jobs
- Modules:
  - Leads
  - Opportunities
  - Contacts
  - Customers
  - Rates
  - Quotes
  - Shipments
  - Carriers
  - Bookings
  - Job Costing
  - Invoices
- Default lead sources:
  - Email
  - Google Ads
  - Website Quote Form
  - Website Contact Form
  - Referral
  - Partner
- Default services:
  - Air Freight
  - Ocean Freight
  - Road Freight
  - Customs Clearance
  - Warehousing
  - Project Cargo

### 2. Container Conversion Company

- Best for: project-based conversion, fabrication, and delivery teams
- Usage metric: Projects
- Modules:
  - Leads
  - Opportunities
  - Contacts
  - Customers
  - Projects
  - Drawings
  - Delivery Tracking
- Default lead sources:
  - Email
  - Website Quote Form
  - Website Contact Form
  - Google Ads
  - Referral
- Default services:
  - Container Conversion
  - Custom Fabrication
  - Design & Drawings
  - Site Installation
  - Modified Containers

### 3. Ship Chandling

- Best for: vessel supply, requisitions, and urgent port-call operations
- Usage metric: Operational orders
- Modules:
  - Leads
  - Opportunities
  - Contacts
  - Customers
  - Vessel Calls
  - Supply Orders
  - Delivery Tasks
- Default lead sources:
  - Email
  - Port Agent
  - Referral
  - Website Contact Form
  - Direct Call
- Default services:
  - Provisions
  - Deck Stores
  - Engine Stores
  - Cabin Stores
  - Safety Equipment
  - Spare Parts

### 4. Shipping Liner

- Best for: liner bookings, slot allocation, and account-based sales
- Usage metric: Bookings
- Modules:
  - Leads
  - Opportunities
  - Contacts
  - Customers
  - Bookings
  - Sailings
  - Customer Accounts
- Default lead sources:
  - Email
  - Sales Team
  - Website Contact Form
  - Referral
  - Partner
- Default services:
  - Container Shipping
  - Reefer Booking
  - Out of Gauge
  - Slot Allocation
  - Contract Rates

### 5. Ship Management Company

- Best for: technical, crewing, procurement, and owner-facing management relationships
- Usage metric: Managed contracts
- Modules:
  - Leads
  - Opportunities
  - Contacts
  - Customers
  - Fleet
  - Technical Management
  - Crewing
- Default lead sources:
  - Email
  - Referral
  - Website Contact Form
  - Conference
  - Partner
- Default services:
  - Technical Management
  - Crew Management
  - Procurement
  - Vessel Accounting
  - HSQE Management

### 6. Container Trading / Leasing

- Best for: stock-led sales, leasing, depot, and allocation workflows
- Usage metric: Commercial deals
- Modules:
  - Leads
  - Opportunities
  - Contacts
  - Customers
  - Inventory
  - Leasing
  - Depots
- Default lead sources:
  - Email
  - Website Quote Form
  - Google Ads
  - Referral
  - Broker
- Default services:
  - Container Sales
  - Container Leasing
  - Storage
  - Depot Handling
  - Used Container Supply

### 7. General Maritime

- Best for: maritime companies that need a lighter CRM-first operating mode
- Usage metric: Operational records
- Modules:
  - Leads
  - Opportunities
  - Contacts
  - Customers
- Default lead sources:
  - Email
  - Google Ads
  - Website Quote Form
  - Website Contact Form
  - Referral
- Default services:
  - Maritime Services
  - Freight Services
  - Container Conversion
  - Port Services
  - Technical Services

## CRM Vocabulary Definitions

Each workspace mode can override CRM wording without changing the underlying logic.

### Lead Status Labels
Typical definitions include:

- In-progress: active qualification or review
- Sales Qualified: ready for the next commercial step
- Disqualified: not moving forward

Mode-specific examples:

- Freight Forwarder: `Qualification`, `Quote Ready`
- Container Conversion: `Initial Review`, `Project Qualified`
- Ship Chandling: `Active Enquiry`, `Requisition Ready`
- Shipping Liner: `Booking Review`, `Space Qualified`
- Ship Management: `Management Review`, `Owner Qualified`
- Container Trading / Leasing: `Commercial Review`, `Stock Qualified`

### Opportunity Stage Labels
The platform maps default opportunity stages into business-specific language. Examples:

- Freight Forwarder: `Enquiry Received`, `Quote Sent`, `Booked`, `Shipment Pending`
- Container Conversion: `Initial Brief`, `Project Awarded`, `Drawings Submitted`, `Project Delayed`
- Ship Chandling: `Vessel Enquiry`, `Requisition Received`, `Supplied`, `Delivery Pending`
- Shipping Liner: `Booking Request`, `Offer Sent`, `Booked`, `Sailing Deferred`
- Ship Management: `Management Enquiry`, `Proposal Sent`, `Contract Signed`, `Handover Pending`
- Container Trading / Leasing: `Asset Enquiry`, `Offer Sent`, `Deal Closed`, `Unit Allocation Pending`

### Disqualification Reasons
Default and mode-specific reasons include:

- No Answer (ONLY SYSTEM)
- Mismatch of Needs
- Duplicate Lead
- Geo Limitations
- Budget mismatch
- Out of delivery window
- No vessel coverage
- Fleet mismatch
- No stock available

## Data Sources and Integrations

### Source Kinds

- Public Google Sheet / CSV
- Google Sheets API
- Uploaded CSV
- CargoWise API

### Supported Source Targets

- Leads
- Opportunities
- Contacts
- Customers
- Quotes
- Shipments
- Carriers
- Bookings
- Reports
- Google Ads

### CargoWise Connection Options

- Basic Auth
- Bearer Token
- No Auth

### CargoWise Response Formats

- JSON
- CSV
- XML

## Pricing and Packaging

### Freemium

- Price: Free
- Included users: 3
- Included operational records: 100
- Includes:
  - 1 workspace
  - 3 users included
  - First 100 operational records included
  - Core CRM, collaboration, and reports

### Growth

- Price: $149 / workspace / month
- Included users: 5
- Included operational records: 500
- Includes:
  - Google Sheets, CSV, and standard APIs
  - Workspace controls and exports

### Professional

- Price: $399 / workspace / month
- Included users: 10
- Included operational records: 1,500
- Includes:
  - Advanced workflow, finance, and segmentation
  - Priority support
  - Migration help
  - Custom branding

### Enterprise

- Price: Custom
- Included users: Custom
- Included operational records: Custom
- Includes:
  - SSO, SLA, and enterprise controls
  - Custom integrations and onboarding
  - Multi-workspace rollout support
  - Enterprise security

### Feature Flag Definitions

- Advanced integrations: higher-tier sync and API support
- Premium support: faster support and onboarding assistance
- Custom branding: customer-specific branding options
- Enterprise security: enterprise-grade identity and control requirements

## End-to-End Workflow

1. Capture demand from forms, sheets, uploads, APIs, or manual entry.
2. Qualify leads and progress opportunities through mode-specific stages.
3. Convert accepted work into bookings, shipment jobs, projects, supply orders, or other operational records.
4. Track costing, invoices, account health, and reporting against the same record trail.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Livewire
- Livewire Flux
- Livewire Volt
- Tailwind CSS 4
- Vite
- Google API Client
- Laravel Socialite
- `jeremykenedy/laravel-roles`

## Local Development

### Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

### Run

```bash
composer run dev
```

That starts:

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `php artisan pail --timeout=0`
- `npm run dev`

### Build Assets

```bash
npm run build
```

### Run Tests

```bash
php artisan test
```

## Repo Notes

- App version is exposed through `config('app.version')`
- Workspace behavior is primarily defined in:
  - `config/workspace_templates.php`
  - `config/pricing.php`
  - `app/Models/Workspace.php`
  - `app/Models/SheetSource.php`
- Super admin management lives in:
  - `app/Livewire/AdminDashboard.php`
  - `resources/views/livewire/admin-dashboard.blade.php`

## Summary

IQX Connect is not just a CRM. It is a multi-workspace maritime operating system that combines:

- business-model-specific workspace templates
- sales and pipeline workflows
- operational execution records
- finance and reporting visibility
- source sync and migration tooling
- platform-wide super admin controls

This README is intended to make the GitHub repository understandable as both a product and a codebase entry point.
