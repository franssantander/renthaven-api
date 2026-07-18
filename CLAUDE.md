# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**RentHaven** is a rental management system (MVP stage) that handles:
- Property management
- Property unit management
- Leases / Renters
- Payment ledger
- Maintenance management
- Dashboard (reporting/aggregated views)

The project is currently in **local development, not yet in production**. This matters for migration strategy (see below).

### Key platform features

- **Magic link payments** — a renter/lease can receive an emailed magic link that lets them view and pay an outstanding charge without logging in. On the admin side, a payment can also be manually marked as paid/updated by an admin. Any work touching payments must account for both paths: the self-serve magic-link flow and the admin-initiated update, and keep the payment ledger consistent between them.
- **Multi-tenant business** — the app is multi-tenant. Tenancy plans and limits are already implemented (plan structure + usage/resource limits per tenant). New features that create records or allow bulk actions should respect the current tenant's plan limits — check the existing plan/limit enforcement mechanism before adding new resource-creation logic.
- **Role & permission system** — permissions are defined per role at the application level (not just DB-driven ad hoc checks). This is enforced via middleware, and the authenticated user's permission set is returned as part of their auth/user data (e.g. on login/me endpoints) for frontend gating. New endpoints should be registered under the correct permission check rather than left open or only guarded by auth.

## Tech Stack

- **Framework:** Laravel 13
- **PHP:** 8.4 (via `php:8.4-fpm` Docker image)
- **Web server:** Nginx
- **Database:** MySQL / MariaDB
- **Auth:** Laravel Passport (stateless — API token grants, no session)
- **Infra:** Docker / docker-compose

## Architecture & Conventions

### Layered structure
- **Controllers are thin.** No business logic in controllers. A controller method should: validate (via Form Request), call a Service method, return a success/error response.
- **Service layer** holds all business logic. One service class per domain/feature (e.g. `PropertyService`, `LeaseService`, `PaymentLedgerService`, `MaintenanceRequestService`, `PropertyUnitService`).
- Controllers depend on services via constructor injection, not facades or static calls.

### Request validation
- Every create/update endpoint gets its own `StoreXRequest` / `UpdateXRequest` (`app/Http/Requests/...`).
- Form Requests must define clear, specific validation messages (`messages()` method) — no relying on default Laravel wording for user-facing errors.
- Authorization checks (`authorize()`) belong in the Form Request when tied to input, otherwise in policies.

### Data layer (Spatie Laravel Data)
- Use `spatie/laravel-data` Data classes for all request/response payload shaping. Do not return raw Eloquent models from controllers.
- Use Spatie's **Paginated Data** classes for any list/index endpoint response — do not hand-roll pagination meta.
- Naming convention: `{Entity}Data`, `{Entity}PaginatedData` (or the project's existing convention — check `app/Data` before creating new ones).

### Enums
- Use PHP enums for any fixed set of values: statuses, types, categories, etc. (e.g. `PropertyType`, `PropertyUnitStatus`, `LeaseStatus`, `PaymentStatus`, `MaintenanceRequestStatus`, `MaintenanceRequestPriority`). Place them in `app/Enum` (singular — matches the existing folder, don't create `app/Enums`).
- Prefer backed enums (`: string` or `: int`) so they serialize cleanly through Spatie Data classes.
- Avoid loose strings/constants for these values anywhere in the codebase.

### Identifiers
- **UUIDs** are used only for identifiers passed in **payloads** — i.e. request params for create, update, and delete operations (route params / body params referencing a resource from the outside). Resolve UUID ↔ internal ID via `app/Support/UuidResolver.php` rather than ad hoc lookups.
- **Foreign keys / internal relations** stay as standard auto-increment DB IDs (`id`, `*_id` FKs). Do not convert relational foreign keys to UUIDs.
- Models exposed via API should have a `uuid` column used for public-facing lookups, resolved internally to the numeric `id` for relations/queries.

### Multi-tenancy
- Tenant-owned models use the `BelongsToTenantBusiness` trait (`app/Traits/BelongsToTenantBusiness.php`) for scoping — apply this trait to new tenant-owned models rather than writing a new global scope from scratch.
- Respect the existing tenant plan/limit enforcement when adding resource-creation logic (check the relevant plan/limit service before allowing creation of a new record type).

### Routing
- Do not add routes directly into `routes/api.php`.
- Each domain gets its own route file under `routes/v1/` (e.g. `routes/v1/properties.php`, `routes/v1/property_unit.php`, `routes/v1/leases.php`, `routes/v1/payments.php`, `routes/v1/maintenance.php`), and `routes/api.php` only imports them.

### Responses
- All controllers extend `app/Http/Controller.php` and use the shared `app/Support/ApiResponder.php` `success()` and `error()` methods for responses. Do not manually build `response()->json()` ad hoc in a controller.
- Use Laravel's built-in `Illuminate\Http\Response` / `Symfony\Component\HttpFoundation\Response` HTTP status constants (e.g. `Response::HTTP_UNPROCESSABLE_ENTITY`, `Response::HTTP_UNAUTHORIZED`) — never hardcode magic numbers like `422` or `401` directly.
- Use Laravel's built-in HTTP status text/messages where applicable instead of custom strings.

### Database migrations
- **Do not create new migrations that append/alter existing tables.** The schema is still fluid in local development (pre-production) — edit the original migration file for the table directly instead of stacking a new migration on top of it.
- This applies until the project ships to production, at which point this rule will change to standard additive migrations.

### Authentication
- Laravel Passport, **stateless**.
- Login endpoint does **not** return a token in the response body/flow the usual Passport way — follow the project's existing stateless auth handling (do not assume standard `access_token` response shape without checking current implementation first).

## Domain Modules (MVP scope)

| Module | Notes |
|---|---|
| Property Management | Parent property records |
| Property Unit Management | Units belonging to a Property (1-to-many) |
| Leases / Renters | Lease agreements tied to a Renter and a Property Unit |
| Payment Ledger | Tracks rent payments/charges against a Lease |
| Maintenance Management | Maintenance requests tied to a Property Unit / Lease |
| Dashboard | Aggregated/reporting views across the above |

## Directory Structure

This reflects the **actual current codebase** (early/initial state — will grow as features are added, but the shape below is the convention to follow):

```
app/
├── Data/                       # Spatie Data classes, grouped per domain
│   └── Property/
│       ├── PropertyData.php
│       └── ...                 # e.g. PropertyPaginatedData.php
├── Enum/                       # NOTE: singular "Enum", not "Enums"
│   ├── PropertyType.php
│   ├── PropertyUnitStatus.php
│   └── ...
├── Http/
│   ├── Controller.php           # base controller (other controllers extend this)
│   ├── PropertyController.php
│   ├── PropertyUnitController.php
│   ├── Requests/                 # StoreXRequest / UpdateXRequest per domain (add as needed)
│   │   └── Property/
│   │       ├── StorePropertyRequest.php
│   │       └── UpdatePropertyRequest.php
│   ├── Middleware/                # add PermissionMiddleware, tenant resolution middleware here
│   └── ...
├── Models/
│   ├── Property.php
│   └── ...
├── Notifications/
│   ├── MagicLinkNotification.php  # emailed payment magic link
│   └── ...
├── Policies/
│   ├── PropertyPolicy.php
│   └── ...
├── Services/                    # grouped per domain, NOT flat
│   └── Ledger/
│       ├── LedgerService.php
│       └── ...
│   # follow this same "Services/{Domain}/{Domain}Service.php" pattern for
│   # Property, PropertyUnit, Lease, MagicLinkPayment, Maintenance, Dashboard,
│   # TenantPlan, Permission, etc.
├── Support/                      # shared low-level helpers (not domain services)
│   ├── ApiResponder.php          # success()/error() response helper — used in place of an abstract controller
│   ├── UuidResolver.php          # resolves public UUIDs <-> internal DB ids for payloads
│   └── ...
└── Traits/
    ├── BelongsToTenantBusiness.php   # multi-tenant scoping trait, used on tenant-owned models
    └── ...

database/
├── factories/
│   ├── TenantBusinessFactory.php
│   └── ...
├── migrations/
│   └── ...                      # edit existing migration files in place (see rule above)
└── seeders/
    └── ...                      # one seeder per feature (see Seeder Requirement below)

routes/
├── v1/
│   ├── properties.php
│   ├── property_unit.php
│   └── ...                      # one file per domain: leases.php, payments.php, maintenance.php, dashboard.php, etc.
└── api.php                      # imports routes/v1/*.php only, no route definitions itself
```

**Notes on conventions to follow, based on this structure:**
- Enums live in `app/Enum` (singular) — match this, don't create `app/Enums`.
- Services are grouped per domain in subfolders (`Services/{Domain}/{Domain}Service.php`), not flat files directly in `Services/`.
- There's no abstract base controller for responses — `app/Support/ApiResponder.php` is the shared success/error responder. `app/Http/Controller.php` is the base controller other controllers extend. Use these existing files rather than introducing a new abstract class.
- UUID ↔ internal ID resolution for payloads goes through `app/Support/UuidResolver.php`.
- Multi-tenant scoping is applied via the `BelongsToTenantBusiness` trait on tenant-owned models, not through a query scope defined ad hoc per model.
- `Http/Requests/` and `Http/Middleware/` aren't populated yet in the initial files shown, but should be created following this same domain-grouped structure as Store/Update requests and permission/tenant middleware are added.

## Before Writing Code

- Check `app/Services`, `app/Data`, `app/Enum`, `app/Http/Requests`, and the relevant `routes/v1/*.php` file for existing patterns before creating new classes — match existing naming and structure rather than introducing a new style.
- Check whether a migration for the target table already exists before creating one; modify it in place if it does (see Database Migrations rule above).
- Reuse `app/Support/ApiResponder.php`, `app/Http/Controller.php`, `app/Support/UuidResolver.php`, and `app/Traits/BelongsToTenantBusiness.php` rather than reinventing equivalents.
- Check current tenant plan/limit enforcement before adding creation logic that could be affected by plan limits.
- Check the permission/role setup before adding a new endpoint, and register it under the correct permission rather than leaving it open.

## Seeder Requirement

**Every feature built must ship with a corresponding database seeder** so it can be tested immediately with realistic data.

- Add or update a seeder under `database/seeders/` for any new table, status/enum set, or feature-specific data (e.g. a new `MaintenanceRequestSeeder` when adding maintenance management, updated `PaymentLedgerSeeder` when adding a new payment state).
- Seeders should cover realistic multi-tenant scenarios (more than one tenant, more than one plan) and, where relevant, multiple roles/permissions so permission-gated behavior can be tested.
- Register new seeders in `DatabaseSeeder.php` so `php artisan migrate:fresh --seed` produces a fully testable dataset in one command.
- Prefer factories + seeders together (factory for shape, seeder for orchestration/scenario data) over hardcoded arrays, unless the data is inherently fixed (e.g. permission names, plan definitions).
