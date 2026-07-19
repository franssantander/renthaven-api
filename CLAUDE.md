# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**RentHaven** is a multi-tenant rental management API (Laravel 13, PHP 8.4, stateless Passport auth). It covers property/unit management, leases, a payment ledger, maintenance requests, magic-link renter payments, a role/permission system, tenant subscription plans, audit logging, and notifications.

The project is in **local development, not yet in production** — this affects the migration strategy (see below).

## Commands

```bash
# Full local setup (copies .env, generates key, migrates, installs/builds JS)
composer run setup

# Run app + queue worker + log tailer (pail) + vite, concurrently
composer run dev

# Run the full test suite
composer run test
# equivalent to:
php artisan config:clear && php artisan test

# Run a single test file / filter by name
php artisan test tests/Feature/SomeTest.php
php artisan test --filter=test_method_name

# Lint / format (Laravel Pint)
vendor/bin/pint
vendor/bin/pint --test   # check only, no writes

# Migrate fresh with all seeders (primary way to get a testable dataset)
php artisan migrate:fresh --seed

# Scaffold a new Action class under app/Actions (custom artisan command)
php artisan make:action Permission/SomeAction
```

Tests run against an in-memory SQLite DB (`phpunit.xml`), independent of the MySQL dev DB in `.env`.

## Architecture

### Layered structure
- **Controllers** (`app/Http/Controllers`) are thin: validate via Form Request → call a Service (or Action) → return via `success()`/`error()` helpers from the base `Controller` (`app/Http/Controllers/Controller.php`, note: under `Controllers/`, not directly in `Http/`).
- **Services** (`app/Services/{Domain}/{Domain}Service.php`) hold business logic, one class per domain (`Property`, `PropertyUnit`, `Lease`, `Ledger`, `MaintenanceRequest`, `Amenity`, `AuditLog`, `Auth`, `Dashboard`, `Notification`, `PropertyAttachment`, `RenterPortal`).
- Controllers depend on Services/Actions via constructor injection.

### Request validation
- Every create/update endpoint has its own `Store{X}Request` / `Update{X}Request` under `app/Http/Requests/{Domain}/`, each with a `messages()` method for user-facing validation text — don't rely on Laravel's default wording.
- `authorize()` on the Form Request is used for input-tied checks; broader authorization goes through Policies (`app/Policies`) or the `permission` route middleware.

### Data layer (Spatie Laravel Data)
- `app/Data/{Domain}/{Entity}Data.php` classes shape all request/response payloads — controllers do not return raw Eloquent models.
- Use Spatie's Paginated Data classes for list/index endpoints rather than hand-rolling pagination meta.

### Enums
- All fixed value sets live in `app/Enum` (singular — don't create `app/Enums`), as backed enums (e.g. `PropertyType`, `PropertyUnitStatus`, `LedgerStatus`, `MaintenanceRequestStatus`, `MaintenancePriority`, `Role`, `AuditAction`).

### Identifiers
- UUIDs are used only in **payloads** (route/body params referencing a resource from outside). Every API-exposed model has a `uuid` column; resolution to the internal `id` goes through `app/Support/UuidResolver.php::id()`/`::ids()` rather than ad hoc lookups.
- Models get UUID support via the `HasHasPublicUuidTrait` trait (`app/HasHasPublicUuidTrait.php` — note: lives directly under `app/`, not `app/Traits`, and keep the existing (misspelled) name rather than "fixing" it, since it's referenced across models).
- Foreign keys / internal relations stay standard auto-increment `id`/`*_id` — never convert these to UUIDs.

### Multi-tenancy
- Tenant-owned models use the `BelongsToTenantBusiness` trait (`app/Traits/BelongsToTenantBusiness.php`). It auto-fills `tenant_business_id` on create and adds a global scope filtering every query to the authenticated user's tenant — except for `Role::SUPER_ADMIN`, who sees everything unscoped. No user (console/queue context) means no scoping is applied.
- **Plan limits are enforced in Policies, not a dedicated service.** E.g. `PropertyPolicy::create()` loads `$user->tenantBusiness->plan` and compares current usage against columns like `plan->max_properties` (see `app/Models/Plan.php` for the fillable limit columns: `max_properties`, `max_units`). Follow this same pattern (Policy `create()` method checking tenant's plan) when adding new resource-creation logic that should respect plan limits — there is no separate `PlanLimitService`.

### Permissions
- Permission model: `PermissionModule` × `PermissionAction` (many-to-many via `permission_module_action`), granted to a `Role` via `RolePermission`, with optional per-user overrides via `PermissionPerUser`.
- `User::hasPermission(string $moduleSlug, string $actionCode): bool` is the check used everywhere.
- Route-level enforcement uses the `permission` middleware (`app/Http/Middleware/CheckPermission.php`), applied per-route as `->middleware('permission:{module},{action}')` (see `routes/v1/properties.php` for examples). New endpoints should be registered under the correct permission rather than left open or only behind `auth:api`.

### Routing
- No route definitions in `routes/api.php` — it only `require`s files under `routes/v1/` (one per domain: `properties.php`, `property_unit.php`, `lease.php`, `ledger.php`, `maintenance_request.php`, `amenities.php`, `permission.php`, `tenant_business.php`, `user_management.php`, `dashboard.php`, `renter_portal.php`, `audit_log.php`, `notifications.php`, `auth.php`).
- Route files group by prefix/name/`auth:api` middleware and a `->controller(...)` group, adding `permission:...` middleware per action as needed.

### Responses
- All controllers use `success()`/`error()` (from the base `Controller`, backed by `app/Support/ApiResponder.php`) — never build `response()->json()` ad hoc.
- `ApiResponder::error()` already maps common exception types (`ValidationException`, `AuthenticationException`, `AuthorizationException`, `ModelNotFoundException`/`NotFoundHttpException`, `QueryException`, `HttpException`) to the right status code and a safe default message, and appends a `debug` block outside production. Pass the caught exception through rather than re-deriving status codes/messages by hand.
- Use `Symfony\Component\HttpFoundation\Response` HTTP status constants, never magic numbers.

### Database migrations
- **Do not append/alter via new migrations while pre-production.** Edit the original migration file for the table directly. This will change to standard additive migrations once the project ships.

### Key platform features to keep in mind
- **Magic link payments**: a renter can pay an outstanding ledger charge via an emailed magic link (`MagicLinkService`, `MagicLinkToken` model, `MagicLinkNotification`) without logging in; admins can also mark a payment as paid manually. Anything touching payments must handle both paths and keep the ledger consistent.
- **Audit logging**: significant actions are recorded via `AuditLog`/`AuditLogService` with `AuditAction`/`AuditModule` enums — check existing usages before adding new mutation endpoints so they're captured consistently.

## Seeder Requirement

**Every feature must ship with a corresponding seeder** so `php artisan migrate:fresh --seed` produces a fully testable dataset in one command (see `database/seeders/DatabaseSeeder.php` for registration order — permissions/roles/plans/tenants before domain data). Seeders should cover multiple tenants/plans and multiple roles so permission-gated and plan-limited behavior can be exercised. Prefer factories (`database/factories`) + seeders together over hardcoded arrays, except for inherently fixed data (permission names, plan definitions).

## Before Writing Code

- Check `app/Services`, `app/Data`, `app/Enum`, `app/Http/Requests`, and the relevant `routes/v1/*.php` file for existing patterns before creating new classes.
- Check whether a migration for the target table already exists before creating one; modify it in place if so.
- Check the relevant Policy for existing plan-limit enforcement before adding creation logic for a new resource type.
- Check `PermissionModuleSeeder`/`PermissionActionSeeder` and the target route file before adding a new endpoint, and gate it with `permission:{module},{action}`.
