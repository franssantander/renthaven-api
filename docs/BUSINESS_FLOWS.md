# RentHaven API — Business Flow Documentation

> **Change history:** see `docs/CHANGES_2026-08-01_backend_audit.md` for a full account of a
> 2026-08-01 audit-and-fix pass across every module below. Several items this document used to
> list under "Known Gaps" are now resolved and marked inline (struck through) rather than removed,
> so the history stays visible.

## Introduction

RentHaven is a multi-tenant rental management platform. A **tenant business** (a landlord/property management company) signs up, subscribes to a **plan**, builds a team of **staff/admin users**, lists **properties** made up of **property units**, and assigns **renters** to units via **leases**. Rent is billed through a recurring **ledger**, renters can report issues through **maintenance requests**, and every meaningful action is recorded to an **audit log** which automatically fans out to an in-app **notification** inbox. A **dashboard** layer aggregates KPIs on top of all of this.

### Key actors (roles)
- **Super Admin** — platform operator; unscoped access across every tenant business.
- **Admin / Staff** — employees of a tenant business; scoped to their own business's data, gated by a role/permission matrix.
- **Renter / Tenant** — the end customer who occupies a unit under a lease; has a restricted self-service view (their own lease, their own ledger, their own maintenance requests) and can log in passwordlessly via a magic link.

### System-wide mechanisms worth understanding before reading module sections
- **Multi-tenancy isolation**: any model using the `BelongsToTenantBusiness` trait auto-stamps `tenant_business_id` on create and applies a global query scope filtering every read to the current user's own tenant business — except for Super Admin, who is unscoped. This is enforced structurally, not per-query, so it's very hard for a new feature to accidentally leak data across tenants.
- **UUIDs vs internal IDs**: every API-facing model has a `uuid` used in routes/payloads; internal foreign keys stay auto-increment `id`. Resolution happens through a shared `UuidResolver`.
- **Permission gating**: nearly every route is protected by `permission:{module},{action}` middleware, resolved against a role-based default matrix with optional per-user overrides (see the Permission module).
- **Audit → Notification pipeline**: business mutations call an `AuditLogger`, which writes an immutable audit row and then automatically fans that event out to the relevant users' notification inboxes. Modules do not raise notifications directly.

---

## 1. TenantBusiness Module

**Purpose:** Represents a rental company/landlord organization — the root multi-tenancy boundary. Every business-scoped resource (users, properties, leases, ledger entries, etc.) hangs off a `TenantBusiness` record, and each business subscribes to a `Plan` that caps how much it can use the system. (Not to be confused with a "renter"/lease-tenant, a separate concept.)

**Key Endpoints** (prefix `/tenant-business`):

| Method | Route | Permission/Auth | Description |
|---|---|---|---|
| POST | `/tenant-business/register` | Public | Self-service sign-up: creates a business + its first Admin user in one transaction |
| GET | `/tenant-business` | `auth:api` + `tenant_business,view` | List businesses (all for Super Admin, own-only otherwise) |
| POST | `/tenant-business` | `auth:api` + `tenant_business,create` | Super-Admin-only manual business creation |
| GET | `/tenant-business/{id}` | `auth:api` + `tenant_business,view` | View one business |
| PUT | `/tenant-business/{id}` | `auth:api` + `tenant_business,update` | Update business profile |
| DELETE | `/tenant-business/{id}` | `auth:api` + `tenant_business,delete` | Delete a business, cascading to its users |

**Business Rules & Logic:**
- **Self-registration flow**: validates a `plan_uuid` plus the business's name/email/phone (globally unique) and the first admin's credentials. In one transaction: creates the business with `status = INACTIVE`, resolves the plan, looks up the `admin` role, and creates the business's first `User` with that role. Fires Laravel's `Registered` event (ties into the email-verification login gate in Auth). Issues its access/refresh-token cookie pair via the shared `TokenService::issue()`, same as a normal login (fixed 2026-08-01 — previously issued a raw Passport token directly, skipping refresh-token creation).
- **Manual creation** (`store`) is restricted to Super Admin only.
- **Scoping**: non-Super-Admin callers can only view/act on their own single business — never a list of others'.
- **Deletion** cascades: deletes all of the business's users first, then the business itself, inside a transaction.
- Validation: `name`, `email`, `phone`, `tin` required and unique per `tenant_businesses`; `status` is `active`/`inactive`.

**Data Model:** `TenantBusiness` (`plan_id`, `name`, `email`, `phone`, `contact_person`, `tin`, `business_address`, `logo`, `status`; soft-deletes) — `hasMany users`, `belongsTo plan`, `hasMany properties`.

**Enums:** `Status`: `active`, `inactive` (also reused on `User.status`).

**Cross-Module Interactions:**
- Every business belongs to exactly one **Plan**, which caps properties/units.
- The `BelongsToTenantBusiness` trait is the structural mechanism used by most other modules' models for isolation.
- Registration creates the business's first **UserManagement** record and assigns the `admin` **Permission** role.
- Registration fires the same email-verification requirement enforced later at **Auth** login.

---

## 2. Plan Module

**Purpose:** Defines subscription tiers (Free/Basic/Pro/Enterprise) that cap how many properties and units a tenant business may create — the platform's monetization/usage-limiting mechanism.

**Key Endpoints:** **None currently reachable.** `PlanController` exists with a working `index()` (`Plan::all()`), but it is **not wired into any route file**, and `create`/`store`/`show`/`update`/`destroy` are empty stubs. Plans today can only be managed via seeders/Tinker, not the API.

**Business Rules & Logic:**
- Seeded tiers:

  | Plan | Price | Max Properties | Max Units |
  |---|---|---|---|
  | Free | ₱0.00 | 2 | 5 |
  | Basic | ₱499.00 | 5 | 10 |
  | Pro | ₱1,499.00 | 20 | 50 |
  | Enterprise | ₱3,999.00 | 9999 | 9999 (effectively unlimited) |

- Enforcement is **not** in a dedicated `PlanService` — it lives in `PropertyPolicy::create()` and `PropertyUnitPolicy::create()`: deny outright if the tenant has no plan; deny with "Plan limit reached..." if `max_properties`/`max_units > 0` and the current (+ incoming) count would exceed it. A limit value of `0` is treated as unlimited (not currently used by any seeded plan).
- **Gap for the analyst**: there is no self-service or admin-facing way to change a business's plan after creation — `UpdateTenantBusinessRequest` does not even accept a `plan_uuid` field.
- This plan-limit pattern currently only covers Properties and Property Units — no other resource type (e.g. staff seats) is plan-capped yet.

**Data Model:** `Plan` (`name`, `slug`, `description`, `price`, `max_properties`, `max_units`) — referenced by `TenantBusiness.plan_id`.

**Cross-Module Interactions:** Consumed by Property and PropertyUnit creation policies; referenced at TenantBusiness registration/creation time.

---

## 3. Permission Module

**Purpose:** A role-based + per-user-override permission system. Nearly every endpoint in the API is gated by a `permission:{module},{action}` route middleware resolved against this module's data. Lets admins view a user's effective permission matrix and grant custom overrides on top of (or instead of) their role's defaults.

**Key Endpoints** (prefix `/permission`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/permission` | `permission_management,view` | List every module + its available actions |
| GET | `/permission/{user}` | `permission_management,view` | A user's effective permission matrix |
| POST | `/permission/sync` | `permission_management,update` | Replace a user's custom permission overrides |
| POST | `/permission/revoke-all` | `permission_management,delete` | Remove all overrides (revert to role defaults) |

**Business Rules & Logic:**
- **Super Admin bypass**: implicitly granted every module/action, no DB rows needed.
- **Override-vs-role resolution**: if a user has ANY active row in `permission_per_user`, their permissions come *exclusively* from that override set (full replacement, not additive) — otherwise they fall back to their role's `role_permissions`.
- **`sync`**: in one transaction, marks all existing override rows inactive, then upserts the newly submitted module/action pairs as active — an idempotent "replace," preserving history rather than hard-deleting old rows.
- **`revokeAll`**: deactivates all override rows, reverting the user to their role's defaults.
- **`show` authorization**: non-Super-Admins may only view permission matrices for users in their own tenant business.
- **`CheckPermission` middleware**: `permission:{module},{action1},{action2},...` — passing ANY one listed action is sufficient (OR logic); 403 if none match, 401 if unauthenticated.

**Data Model:**
- `PermissionModule` (name, slug) — belongsToMany `PermissionAction`.
- `PermissionAction` (name, slug).
- `RolePermission` — default grant matrix per role.
- `PermissionPerUser` (user_id, module_id, action_id, `is_active`) — per-user override matrix.

**Enums / seed data:**
- **Modules**: `dashboard`, `ledger`, `payment_approvals`, `properties`, `renter_tenants`, `user_management`, `permission_management`, `tenant_business`, `maintenance`, `audit_logs`.
- **Actions**: `view`, `create`, `update`, `delete`, `approve`, `export`, `restore`.
- **Default role presets**: `staff` (view-only/partial, no `user_management`), `admin` (near-full CRUD incl. `user_management`, `permission_management` view/create/update only, `audit_logs` view only), `super_admin` (everything).

**Cross-Module Interactions:** Backbone gating virtually every module's routes. `User::hasPermission()`/`getPermissionMatrix()` are called from Auth (`/auth/me`) to hand the frontend the caller's full capability list on session load.

---

## 4. Auth Module

**Purpose:** Authentication for two audiences — staff/business users (username+password via Laravel Passport) and renters (passwordless magic-link email sign-in) — plus password reset, email verification, and cookie-based access/refresh token issuance and rotation.

**Key Endpoints** (prefix `/auth`):

| Method | Route | Auth | Description |
|---|---|---|---|
| POST | `/auth/login` | Public | Username/password login; sets `auth_token` + `refresh_token` cookies |
| GET | `/auth/email/verify/{id}/{hash}` | Signed URL | Confirms email verification |
| POST | `/auth/refresh` | Public (needs refresh cookie) | Rotates refresh token → new cookie pair |
| POST | `/auth/forgot-password` | Public | Sends password-reset email |
| POST | `/auth/reset-password` | Public | Consumes reset token, updates password, revokes all tokens |
| POST | `/auth/magic-link` | Public | Requests a renter magic sign-in link |
| POST | `/auth/magic-link/verify` | Public | Consumes magic-link token, logs the renter in |
| GET | `/auth/me` | `auth:api` | Current user profile + full permission matrix |
| POST | `/auth/logout` | `auth:api` | Revokes current access token + paired refresh token |

**Business Rules & Logic:**
- **Login**: `Auth::attempt()`; on failure, logs `LOGIN_FAILED` and throws a generic "credentials are incorrect" error (does not reveal whether the username exists).
- **Email verification gate**: an otherwise-correct login is rejected (logged out again, `LOGIN_BLOCKED_UNVERIFIED`) if the user hasn't verified their email — unverified users cannot obtain tokens even with correct credentials.
- **Token issuance** (`TokenService::issue`): creates a Passport access token AND a separate custom `RefreshToken` DB row (SHA-256 hashed, 30-day expiry). Both delivered as httpOnly, Strict-SameSite cookies (`auth_token` 7 days path `/`; `refresh_token` 30 days, scoped to `/api/v1/auth`), never in the JSON body.
- **Refresh/rotation**: validates the SHA-256 hash of the submitted cookie against an active (unrevoked, unexpired) `RefreshToken`; on success, revokes it and its paired access token, then issues a brand-new pair — full single-use rotation. Reused/invalid tokens are rejected (`TOKEN_REFRESH_FAILED`, 401).
- **Logout**: revokes the current access token and its paired refresh token, clears both cookies.
- **Magic link** (renter-only): only issued if a user exists with that email **and has a renter profile**; response message is identical whether or not a match exists (prevents user enumeration). A 64-char random token is generated, only its bcrypt hash stored, 15-minute expiry, single-use (`used_at`).
- **Password reset**: standard Laravel broker; on success, revokes ALL of the user's Passport tokens and custom refresh tokens — forces re-login everywhere after a password change.
- Every significant event is audit-logged under `AuditModule::AUTH` (`LOGIN_SUCCESS`, `LOGIN_FAILED`, `LOGIN_BLOCKED_UNVERIFIED`, `MAGIC_LINK_REQUESTED`, `MAGIC_LINK_LOGIN_SUCCESS`, `TOKEN_REFRESHED`, `TOKEN_REFRESH_FAILED`).

**Data Model:** `User` (Passport `HasApiTokens`, verifiable email), `MagicLinkToken` (`user_id`, hashed token, `expires_at`, `used_at`), `RefreshToken` (`user_id`, `access_token_id`, hashed token, `expires_at`, `revoked_at`).

**Cross-Module Interactions:** `/auth/me` pulls the full permission matrix (Permission module). Magic-link login is the entry point into the **RenterPortal** flow. TenantBusiness registration issues its tokens through the same `TokenService` as a normal login (see TenantBusiness module note).

---

## 5. UserManagement Module

**Purpose:** CRUD for staff/business-user accounts (property managers, admins, staff) within a tenant business — distinct from renter accounts. Where a tenant admin builds their internal team.

**Key Endpoints** (prefix `/user-management`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/user-management` | `user_management,view` | Paginated list, tenant-scoped (or all, for Super Admin) |
| POST | `/user-management` | `user_management,create` | Create a staff user |
| GET | `/user-management/{id}` | `user_management,view` | Show one user |
| PUT | `/user-management/{id}` | `user_management,update` | Update a user |
| DELETE | `/user-management/{id}` | `user_management,delete` | Soft-delete a user |

**Business Rules & Logic:**
- **Tenant scoping** repeated inline across `index`/`show`/`update`/`destroy`: non-Super-Admins only see/act on users in their own tenant business (no shared Policy class — logic is duplicated per method, unlike Property/Lease).
- **Create**: `role_uuid` resolved to `role_id`. Non-Super-Admin creators are forced onto their own tenant business; Super Admins must explicitly supply `tenant_business_uuid`.
- **Update**: same cross-tenant guard. Password optional — blank means unchanged. Non-Super-Admins cannot move a user to a different tenant business.
- **Delete**: same tenant guard, plus a self-protection rule — a user cannot delete their own account (400).
- Validation: `username`/`email` globally unique; password uses `Password::defaults()` policy; `tenant_business_uuid` conditionally required only for Super-Admin callers. `role_uuid` cannot be set to `super_admin` unless the acting caller already is one (fixed 2026-08-01 — previously any admin could self-elevate or elevate another user to Super Admin, which bypasses tenant scoping entirely).
- Every create/update/delete is audit-logged under `AuditModule::USER_MANAGEMENT`.

**Data Model:** `User`, `Role`, `TenantBusiness`.

**Cross-Module Interactions:** Every created user gets a `role_id` (Permission), which can later be overridden per-user via Permission's `sync`/`revokeAll`. Users are tenant-scoped to a `TenantBusiness` (and indirectly its `Plan`).

---

## 6. Property Module

**Purpose:** Manages the physical real-estate assets (a building, dorm, complex) that contain one or more units. Handles CRUD, amenity tagging, and image attachments, enforcing plan limits on property count.

**Key Endpoints** (prefix `/property`, all `auth:api`; `store`'s plan-limit enforcement is a Gate check inside the FormRequest, in addition to the route-level permission below):

| Method | Route | Permission/Auth | Description |
|---|---|---|---|
| GET | `/property` | `properties,view` | List properties for the caller's tenant business |
| POST | `/property` | `properties,create` + Gate-checked (plan limit) | Create a property |
| GET | `/property/{id}` | `properties,view` | Show a property |
| PUT | `/property/{id}` | `properties,update` | Update a property |
| PUT | `/property/{id}/amenities` | `properties,update` | Replace the property's full amenity set |
| POST | `/property/{id}/attachments` | `properties,create` | Upload up to 10 images |
| DELETE | `/property/{id}/attachments/{aid}` | `properties,delete` | Delete an image |
| DELETE | `/property/{id}` | `properties,delete` | Soft-delete a property |
| GET | `/property/dashboard` | `properties,view` | Total Properties / Total Units / Available Units |

**Business Rules & Logic:**
- **Plan limit** (`PropertyPolicy::create`): denies if the tenant has no plan, or if `max_properties > 0` and the current count is already at/over that cap — enforced via a Gate check inside `StorePropertyRequest::authorize()`, rejecting before any DB write.
- `PropertyPolicy`'s `view`/`update`/`delete`/`viewAny` all hard-return `false` and are unused dead code — those actions are authorized entirely via route `permission:` middleware instead (as of 2026-08-01, every mutating route above is gated this way; previously most had no `permission:` middleware at all).
- `name` must be **globally** unique across `properties` (not scoped per tenant) — a naming-collision risk across different tenant businesses worth flagging.
- `tenant_business_uuid` auto-injected from the caller's own business unless the caller is Super Admin.
- Amenity sync fully **replaces** (not merges) the property's amenity list; tenant-scoped (404 if cross-tenant).
- Attachments: max 10 images per call, ≤5MB each; deletion validates the attachment actually belongs to this property before deleting.
- Deleting a property logs its full attribute snapshot to the audit trail first.

**Data Model:** `Property` (belongs to `TenantBusiness`; has many `PropertyUnit`, `amenities`, `attachments`).

**Enums:**
- `PropertyType`: `dorm`, `apartment`, `condo`, `town_house`.
- `AmenityCategory` (shared with Amenity module): `general`, `kitchen`, `bathroom`, `outdoor`, `parking`, `safety`, `internet`, `entertainment`.

**Cross-Module Interactions:** Contains PropertyUnits; Lease creation validates a unit belongs to a property under the caller's tenant. Uses the shared `PropertyAttachmentService`. Consumes a `Plan`'s `max_properties` cap.

---

## 7. PropertyUnit Module

**Purpose:** Manages individual rentable units within a property — the actual leasable inventory (capacity, rent price, occupancy status, amenities, images). The object Leases attach to and Ledger billing derives its amount from.

**Key Endpoints** (prefix `/property-unit`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/property-unit` | `properties,view` | Paginated list, tenant-scoped |
| POST | `/property-unit` | `properties,create` | Create one or more units (plan-limit gated) |
| GET | `/property-unit/{id}` | `properties,view` | Show a unit |
| PUT | `/property-unit/{id}` | `properties,update` | Update a unit |
| PUT | `/property-unit/{id}/amenities` | `properties,update` | Replace a unit's amenity set |
| POST | `/property-unit/{id}/attachments` | `properties,create` | Upload up to 10 images |
| DELETE | `/property-unit/{id}/attachments/{aid}` | `properties,delete` | Delete an image |
| DELETE | `/property-unit/{id}` | `properties,delete` | Delete a unit |
| GET | `/property-unit/dashboard` | — | Counts by status |

**Business Rules & Logic:**
- **Plan limit** (`PropertyUnitPolicy::create`): denies if no plan, or if `max_units > 0` and `currentCount + incomingCount > max_units` — checked against the **total incoming count** for bulk creation in one call.
- A single-unit payload is normalized into a `units` array of one, so the endpoint accepts single or bulk creation.
- Unit `name` must be distinct within the request **and** unique per property (fixed 2026-08-01 — previously enforced globally across `property_units`, so once any tenant used a name like "Unit 1" no other tenant/property could ever reuse it).
- `status` is largely **not** meant to be set directly by users in normal flow — it's recalculated automatically by Lease and MaintenanceRequest services (see below), even though the update request technically permits any change.
- **Tenant isolation**: `PropertyUnit` has no `tenant_business_id` column of its own (tenancy is derived through its `property`), so it can't use the `BelongsToTenantBusiness` trait directly. It now carries an equivalent hand-written global scope (added 2026-08-01) filtering every query through the `property` relation, super-admin-aware — before this fix there was **no tenant isolation on this model at all**, and `update`/`storeAttachments`/`destroyAttachment` had no ownership check of any kind, making any unit in the system editable by UUID from any tenant.
- `show`/`destroy` no longer call `$this->authorize()` (removed 2026-08-01 — the app's base `Controller` doesn't include Laravel's `AuthorizesRequests` trait, so these calls fatally errored on every request; `PropertyUnitPolicy` also never defined the `view`/`delete` methods they referenced). Both actions are now authorized the same way as the rest of this controller: route `permission:` middleware plus the tenant-scoping global scope above.

**Data Model:** `PropertyUnit` (belongs to `Property`; has many `Lease`, `MaintenanceRequest`, `amenities`, `attachments`).

**Enums (`PropertyUnitStatus`):**
- `available` — no active leases.
- `occupied` — active lease count has reached capacity.
- `partially_occupied` — some but not all capacity slots filled.
- `full` — defined but never actually produced by current service logic (possibly reserved for future use).
- `maintenance` — forced status while urgent/high-priority maintenance work is in progress, overriding the occupancy-derived status.

**Cross-Module Interactions:**
- **Lease → PropertyUnit**: `LeaseService::recalculateUnitStatus()` sets status from active lease count vs. capacity on every assign/reassign.
- **MaintenanceRequest → PropertyUnit**: forces `maintenance` status while an `IN_PROGRESS` request with `HIGH`/`URGENT` priority exists, then delegates back to Lease's recalculation once cleared.
- **Ledger**: rent amount for generated entries is pulled directly from `rent_price`.
- Consumes a `Plan`'s `max_units` cap.

---

## 8. Amenity Module

**Purpose:** A catalog of amenity tags (e.g. "Swimming Pool", "Parking") describing properties/units — a two-tier catalog: a global catalog (curated by Super Admin) and per-tenant custom tags.

**Key Endpoints** (prefix `/amenity`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/amenity` | `properties,view` | List global + the caller's tenant-custom amenities, filterable by category |
| POST | `/amenity` | `properties,create` + role-checked in FormRequest (super_admin/admin only) | Create one or many amenity tags |
| PUT | `/amenity/{id}` | `properties,update` + ownership-checked in controller | Update an amenity |
| DELETE | `/amenity/{id}` | `properties,delete` + ownership-checked in controller | Soft-delete an amenity |

**Business Rules & Logic:**
- Create/update/delete are now gated by `permission:{module},{action}` middleware like every other module (added 2026-08-01 — previously relied solely on a role check in the FormRequest plus a controller-level ownership check; `destroy` in particular had no role gating of any kind, so any authenticated user, including a renter, could delete an amenity).
- Only `super_admin` may create a global entry (`tenant_business_id = null`); any other role creates a private tenant-scoped tag.
- Slug is optional and auto-generated (`Str::slug`), must be globally unique.
- `authorizeOwnership()`: a Super Admin may only modify global entries; anyone else only their own tenant's entries — cross-tenant editing blocked with 403.
- Deletes are soft deletes — historical references (e.g. on properties) remain intact.

**Data Model:** `Amenity` (`name`, `slug`, `category`, `icon`, `tenant_business_id` nullable).

**Enums (`AmenityCategory`):** `general`, `kitchen`, `bathroom`, `outdoor`, `parking`, `safety`, `internet`, `entertainment`.

**Cross-Module Interactions:** Attached to Property and PropertyUnit records. Role-gated by the `Role` enum rather than the Permission module's matrix.

---

## 9. PropertyAttachment Module

**Purpose:** Generic polymorphic image/file-attachment handling. There is no dedicated controller — the endpoints live on `PropertyController` and `PropertyUnitController`, both delegating to a shared `PropertyAttachmentService`, which is also reused by Ledger for payment-proof uploads.

**Key Endpoints:** (see Property and PropertyUnit sections above for the actual routes — `/properties/{id}/attachments` and `/property-units/{id}/attachments`, both POST to upload and DELETE `.../{attachmentId}` to remove.)

**Business Rules & Logic:**
- Up to 10 images per upload, each ≤5MB, optional per-image `captions` array matched positionally.
- Storage wrapped in a DB transaction; new attachments get sequential `sort_order` values appended after existing ones (preserves upload order, never overwrites positions).
- Files stored on the `public` disk under `property-attachments/`; each row records filename, MIME type, size, caption, uploader.
- `PropertyAttachment.url` is a computed attribute — always exposes a ready-to-use public URL.
- **Deletion order issue**: the service deletes the physical file **before** soft-deleting the DB row — meaning a soft-deleted attachment can never be restored with its file intact (a data-integrity gap worth flagging if "undo delete" is ever expected).
- Ownership check before delete (`abort_unless` on `attachable_type`/`attachable_id` match) prevents deleting another entity's attachment by guessing an ID.
- Every attach/detach writes an audit log entry with the new `attachment_ids` recorded for create.

**Data Model:** `PropertyAttachment` (polymorphic `attachable` — `Property`, `PropertyUnit`, or `LedgerEntry`; belongs to uploader `User`; soft-deletes; appended `url`).

**Cross-Module Interactions:** Used by Property, PropertyUnit (photo galleries), and Ledger (proof-of-payment uploads on renter payment submission).

---

## 10. Lease Module

**Purpose:** Manages the tenant-to-unit assignment lifecycle — assigning renters to a unit (creating leases) and reassigning an active lease to a different unit ("a move"), while preserving a full occupancy-history timeline. The core linkage between renters and the units they occupy, and the driver of unit occupancy status.

**Key Endpoints** (prefix `/lease`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/lease` | `renter_tenants,view` | (Stub — not implemented) |
| POST | `/lease` | `renter_tenants,create` | Assign one or more tenants to a unit |
| PUT | `/lease/{id}` | `renter_tenants,update` | Reassign an active lease's renter to a new unit |

**Business Rules & Logic:**
- Accepts existing tenants (by UUID) or brand-new tenants (name/email/phone) in the same request, normalized to an array.
- `term_type` is `fixed_term` (requires an `end_date` after `start_date`) or `monthly` (no end date allowed).
- **Capacity check** (`LeasePolicy::create`): denies if `currentActiveCount + incomingTenantCount > unit.capacity` — prevents overbooking a unit.
- New tenants: looked up by email first; if that email already belongs to a **different** tenant business, the request is rejected (422) — prevents identity cross-pollination between tenant businesses. Genuinely new tenants get an unverified `User` (Tenant role) with a random password and a `SetPasswordNotification` so they can self-activate.
- A `Renter` profile is found-or-created per user per tenant business.
- **Reassignment**: only allowed if the lease `is_active`; reassigning to the same unit short-circuits with a friendly message. Also capacity-gated (re-checked under a row lock inside the transaction as of 2026-08-01, closing a TOCTOU race where two concurrent requests could both pass the pre-transaction capacity check). A renter cannot be assigned while they already have another active lease (guard added 2026-08-01). The old lease is ended (`end_date` = move date, `is_active=false`); the new lease keeps the original `end_date` if the term was `fixed_term` (term does not restart), or has none if `monthly`. Reassignment/termination dates are validated to prevent a negative-length lease (`move_date` before the original `end_date`; `move_out_date` on/after `start_date` — added 2026-08-01).
- Every assignment/reassignment writes a `LeaseHistory` row (action, from/to unit, previous lease, performer) — a full occupancy audit trail independent of the general audit log.
- After every change, `LeaseService::recalculateUnitStatus()` recomputes the unit's occupancy status; this call now runs **inside** the same DB transaction as the lease mutation (fixed 2026-08-01 — previously ran after commit, so a crash in between could leave the unit's status stale).

**Data Model:** `Lease` (belongs to `PropertyUnit`, `Renter`); `LeaseHistory` (references `Lease`, from/to unit, performer); `Renter` (belongs to `User` and `TenantBusiness`).

**Enums:**
- `LeaseTermType`: `fixed_term`, `monthly`.
- `LeaseHistoryAction`: `assigned`, `reassigned`, `ended` (the `ended` value is defined but never currently written by any code path).

**Cross-Module Interactions:**
- Drives **PropertyUnit** status (`recalculateUnitStatus`), also reused by **MaintenanceRequest** when clearing a maintenance flag.
- **Ledger** iterates active leases to generate rent billing entries.
- **MaintenanceRequest** creation for tenant users pulls the renter's active lease to attach the request to.

---

## 11. Ledger Module (Billing)

**Purpose:** Manages rent billing — generating periodic rent charges per active lease, tracking payment status, handling renter self-reported payments with proof images, admin approval/rejection, overdue detection, and reminder notifications.

**Key Endpoints** (prefix `/ledger`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/ledger/mine` | — | Renter's own ledger entries |
| PUT | `/ledger/mine/{id}/submit-payment` | — (ownership enforced in-controller) | Renter self-reports payment with proof |
| GET | `/ledger/dashboard` | `renter_tenants,view` | Collected/pending/overdue sums, counts |
| GET | `/ledger` | `renter_tenants,view` | Full tenant-business ledger, filterable |
| PUT | `/ledger/{id}/pay` | `renter_tenants,update` | Admin marks an entry paid |
| PUT | `/ledger/{id}/reject` | `renter_tenants,update` | Admin rejects a submitted payment claim |

**Business Rules & Logic (status transitions):**
- **Generation** (scheduled): for every active lease, finds the latest entry by `period_end`; next period starts the day after (or the lease's `start_date` if none exists yet). Only generates once a period has actually started (no far-future pre-generation). For fixed-term leases, stops once a period would start after the lease's `end_date`. Each period is exactly one calendar month; `amount = unit.rent_price`; initial `status = PENDING`.
- **Overdue sweep** (scheduled): any `PENDING` entry past its `due_date` flips to `OVERDUE`, triggering a one-time reminder notification (guarded by `reminder_sent_at` to prevent duplicates) with a magic-link deep-link.
- **Admin marks paid**: blocked if already `PAID` (422). Sets `PAID`, `paid_at`, `paid_by`, optional notes. Used for both direct admin-recorded payments and approving a renter's submitted claim — one code path guarantees the Monthly Revenue dashboard metric is never double-counted or divergent between the two flows. The read-modify-write on the entry's balance runs inside a `DB::transaction()` with `lockForUpdate()` (added 2026-08-01, closing a race where two concurrent payments on the same entry could clobber each other and consume two OR numbers for one paid entry). An `amount` exceeding the outstanding balance is now rejected (422) rather than silently clamped.
- **Renter submits payment**: only allowed while `PENDING`/`OVERDUE`/`PARTIALLY_PAID` (422 otherwise, re-checked under a row lock as of 2026-08-01); ownership enforced (must be the caller's own entry); requires a proof image (≤5MB). Sets `SUBMITTED`, `submitted_at`, reference/notes, attaches the proof via `PropertyAttachmentService` — the claim update and the proof-image attach now happen inside one transaction (fixed 2026-08-01, previously could leave a `SUBMITTED` entry with no proof on file if the attach step failed).
- **Admin rejects**: only allowed if `SUBMITTED` (422 otherwise). Reverts to `OVERDUE` (if due date passed) or `PENDING` (otherwise); clears submission fields; records a rejection reason.
- Transition map: `PENDING`/`OVERDUE` → `SUBMITTED` (renter) → `PAID` (admin approves) or back to `PENDING`/`OVERDUE` (admin rejects). `PENDING` → `OVERDUE` automatically.

**Data Model:** `LedgerEntry` (belongs to `Lease`, `Renter`, `PropertyUnit`, `TenantBusiness`; has many polymorphic `attachments`; `paid_by` references a staff `User`).

**Enums (`LedgerStatus`):** `pending`, `overdue`, `submitted`, `paid` (final).

**Cross-Module Interactions:**
- **Lease**: entries generated only from active leases; term/end-date bounds generation.
- **PropertyUnit**: `rent_price` is the amount source for new entries.
- **PropertyAttachment**: reused for proof-of-payment uploads.
- **Auth (MagicLinkService)**: overdue reminders deep-link the renter in without a separate login.
- **Dashboard**: "Pending Approvals" and "Monthly Revenue" widgets read directly from ledger status/amounts.

---

## 12. MaintenanceRequest Module

**Purpose:** Lets tenants (or staff on their behalf) report maintenance issues tied to their lease/unit, and lets staff triage, assign, and resolve them — automatically flagging the affected unit as under active maintenance when urgent work is in progress.

**Key Endpoints** (prefix `/maintenance-request`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| POST | `/maintenance-request` | — (role-based branching in-controller) | File a request |
| GET | `/maintenance-request/mine` | — | The caller's own requests as a renter |
| GET | `/maintenance-request/dashboard` | `maintenance,view` | Counts (Total, Open, In Progress, Resolved, Needs Attention) |
| GET | `/maintenance-request` | `maintenance,view` | Tenant-business-wide list, filterable |
| PUT | `/maintenance-request/{id}/status` | `maintenance,update` | Staff transitions status, assigns, adds notes |
| GET | `/maintenance-request/{id}` | — (ownership enforced in-controller) | Show one request + full history |

**Business Rules & Logic:**
- **Store — role-based lease resolution**: a `tenant` caller cannot specify a `lease_uuid` (prohibited) — the controller resolves their own renter profile and active lease instead (422 if either is missing). Non-tenant (staff) callers must supply a `lease_uuid` under their own tenant business — allowing staff to file on a renter's behalf.
- New requests always start `OPEN`; a history row is written with action `created`.
- **Status update**: tenant-business ownership enforced (via the model's own tenant scope as of 2026-08-01; the previous manual `tenant_business_id` equality check broke for super admins). `resolved_at` auto-stamped when transitioning to `RESOLVED`. History action derived by rule: `RESOLVED`→`resolved`, `CANCELLED`→`cancelled`, otherwise `assigned` (if an assignee was set) or `status_changed`. A notification is sent to the renter on every status update. Reassigning `assigned_to` now requires the assignee to belong to the same tenant business (added 2026-08-01).
- `resolved`/`cancelled` are terminal — attempting to transition out of either now returns 422 (guard added 2026-08-01; previously a resolved/cancelled request could be silently reopened).

**Data Model:** `MaintenanceRequest` (belongs to `PropertyUnit`, `Lease`, `Renter`, `TenantBusiness`; `assigned_to` references staff `User`; has many `MaintenanceRequestHistory`).

**Enums:**
- `MaintenanceRequestStatus`: `open`, `in_progress`, `resolved`, `cancelled`.
- `MaintenancePriority`: `low`, `medium` (default), `high`, `urgent` — `high`/`urgent` + `in_progress` together force the unit into `maintenance` status.
- `MaintenanceCategory`: `plumbing`, `electrical`, `hvac`, `appliance`, `structural`, `pest_control`, `other`.
- `MaintenanceRequestHistoryAction`: `created`, `assigned`, `status_changed`, `resolved`, `cancelled`.

**Cross-Module Interactions:**
- **Lease**: every request is tied to a specific lease at creation; tenants may only file against their own active lease.
- **PropertyUnit**: every status update calls `syncUnitMaintenanceStatus`, forcing `maintenance` status while urgent work is in progress, then delegating back to `LeaseService::recalculateUnitStatus()` to restore the normal status — a direct code dependency on the Lease module's service.

---

## 13. RenterPortal Module

**Purpose:** The renter-facing self-serve dashboard — a single screen showing a renter's current lease/unit/property and payment history, scoped strictly to their own tenancy.

**Key Endpoints:**

| Method | Route | Auth | Description |
|---|---|---|---|
| GET | `/renter-portal/dashboard` | `auth:api` | Renter's active lease + paginated ledger history |

Note: this route **requires** `auth:api` like everything else — the magic link is only a substitute for a password at the **login** step (`/auth/magic-link*`, both public); once verified, the renter holds a normal access/refresh-token session and calls this and other endpoints exactly like a password-authenticated user.

**Business Rules & Logic:**
- Resolves the caller's `renterProfile`; 404 if the account has no linked renter record (protects staff/admin accounts from hitting this meaningfully).
- Returns the renter's `activeLease` (with property/unit eager-loaded) and all `LedgerEntry` rows for that renter, ordered by `due_date` desc, paginated.

**The end-to-end magic-link payment flow** (ties together Auth, RenterPortal, and Ledger):
1. **Request** (`POST /auth/magic-link`, public): looks up a user by email that has a renter profile; silently no-ops (same generic message) for non-renters/unknown emails — prevents enumeration.
2. **Token issuance**: 64-char random token, only its hash stored, 15-minute expiry.
3. **Delivery**: emailed link to the frontend's verify page; expires in 15 minutes, single-use.
4. Audit-logged: `MAGIC_LINK_REQUESTED`.
5. **Verification** (`POST /auth/magic-link/verify`, public): finds the matching unused/unexpired token via hash comparison, marks it used (single-use enforcement); invalid/expired/reused → 422.
6. **Session issuance**: on success, logs `MAGIC_LINK_LOGIN_SUCCESS` and issues normal access/refresh cookies via `TokenService` — same as password login from this point on.
7. **Renter submits a payment** (Ledger): moves an entry to `SUBMITTED` with proof, into a pending-admin-review queue (mirrored by the Dashboard's "Pending Approvals" widget).
8. **Admin approves or rejects** (Ledger): both paths converge on the same `markPaid`/`rejectPaymentClaim` state machine, so revenue reporting can't drift between an admin-recorded payment and a renter-initiated one.
9. Every step 7–8 action is audit-logged under `AuditModule::BILLING`, which (via the Notification pipeline) notifies the renter when an admin approves/rejects, and notifies co-admins/staff when the renter submits.

**Data Model:** `Renter` (belongs to `User`; `hasOne activeLease` filtered on `is_active=true`), `Lease` → `PropertyUnit` → `Property`, `LedgerEntry`, `MagicLinkToken`.

**Cross-Module Interactions:** Depends on Auth's magic-link login, Lease's active-lease relation, and Ledger's payment lifecycle — this module is primarily a read-aggregation + entry point rather than owning independent business logic.

---

## 14. AuditLog Module

**Purpose:** An immutable, append-only compliance/activity trail. Records every meaningful create/update/delete and auth event across the platform, scoped per tenant business, so admins can answer "who did what, when." Also the single upstream trigger for the Notification module.

**Key Endpoints** (prefix `/audit-logs`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/audit-logs/mine` | — (any authenticated user) | Caller's own audit trail (used by renters) |
| GET | `/audit-logs` | `audit_logs,view` | Paginated, filterable trail (admin/staff/super admin) |
| GET | `/audit-logs/{id}` | `audit_logs,view` + in-service `canView()` | Single entry detail |

**Business Rules & Logic:**
- **Read-only/immutable** — no update/delete route exists at all; explicitly documented as such.
- **Visibility scoping**: Super Admin sees everything; renters see only rows where `user_id` is their own; admin/staff see all rows for their own tenant business.
- Filters: `module`, `action`, `user_uuid`, `date_from`/`date_to`, free-text `search` (description/actor email), and (Super Admin only) `tenant_business_uuid`.
- **What triggers a write**: an `AuditLogger::record()` call from Amenity, Auth, Lease, Ledger, MaintenanceRequest, Property, PropertyUnit, and UserManagement controllers on create/update/delete/auth events. Captures actor, tenant, module/action enums, a human-readable description, before/after value diffs (auto-derived from the model's changed attributes, with sensitive fields like `password` stripped), the polymorphic record acted on, free-form context, IP, and user agent.
- **Best-effort notification fan-out**: immediately after writing, calls the Notification module inside a try/catch — a notification failure is swallowed (logged, not raised) so it can never roll back or block the underlying business action. A deliberate resilience decision: compliance recording must never be lost due to a notification bug.

**Enums:**
- `AuditModule`: `auth`, `rentals`, `user_management`, `billing`, `tenant_business`, `property`, `property_unit`, `lease`, `amenity`, `maintenance_request` (note: `rentals` and `tenant_business` are defined but not currently written by any controller).
- `AuditAction`: auth-specific values (`login_success`, `login_failed`, `login_blocked_unverified`, `logout`, `magic_link_requested`, `magic_link_login_success`, `token_refreshed`, `token_refresh_failed`) plus generic `created`, `updated`, `deleted` used across all business modules.

**Cross-Module Interactions:** Written to by nearly every mutating module; read exclusively by Notification, which derives every notification from an audit row.

---

## 15. Notification Module

**Purpose:** In-app notification inbox for every user. Notifications are never raised ad hoc by business code — they are automatically fanned out from the audit trail, guaranteeing consistent coverage without relying on each new feature remembering to notify the right people.

**Key Endpoints** (prefix `/notifications`, all `auth:api`, no extra permission gate — every user only manages their own):

| Method | Route | Description |
|---|---|---|
| GET | `/notifications` | Paginated list, newest first; `?unread_only=1` filter |
| GET | `/notifications/unread-count` | Badge count |
| PUT | `/notifications/read-all` | Bulk mark all read |
| PUT | `/notifications/{id}/read` | Mark one read (ownership-checked, 404 otherwise) |

**Business Rules & Logic (`dispatchFromAuditLog`):**
1. Triggered synchronously right after an audit row is written.
2. **Filtering**: the `auth` module is muted entirely (no notification noise from logins/tokens); only `created`/`updated`/`deleted` actions are notifiable.
3. **Recipient targeting**: Super Admin actor → notifies only other Super Admins. Renter actor → notifies the admins/staff of their own tenant business. Admin/staff actor → notifies co-admins/staff of the same business (excluding the actor) *plus* the affected renter if the record (LedgerEntry/Lease/MaintenanceRequest) belongs to their lease — e.g. "your payment was approved" reaches the renter even though an admin performed the action. Recipients are de-duplicated.
4. **Row creation**: stores recipient, tenant, module/action, a generated title (e.g. "Billing Updated"), the audit log's description as the message, and a JSON `data` blob linking back to the source audit row and actor details.
5. **Delivery**: purely in-app/database today — a `NotificationCreated` event is dispatched but has no real-time broadcast implementation yet (explicitly flagged in code as a placeholder for a future websocket push). No email/SMS channel exists for this module (distinct from the separate `MagicLinkNotification`, which does send email).

**Data Model:** `Notification` (belongs to `User` recipient, `AuditLog` source, `TenantBusiness`).

**Cross-Module Interactions:** Entirely downstream of AuditLog — effectively every mutating module indirectly triggers notifications through this single choke point rather than having per-module custom notification logic.

---

## 16. Dashboard Module

**Purpose:** Aggregated KPI widgets for the admin/staff/Super Admin landing page — portfolio size, occupancy, and cash-flow health, plus quick-access lists so admins don't have to dig through the full ledger UI.

**Key Endpoints** (prefix `/dashboard`, all `auth:api`):

| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/dashboard` | `dashboard,view` | Total Properties, Active Tenants, Pending Approvals, Monthly Revenue |
| GET | `/dashboard/pending-approvals` | `payment_approvals,view` | Paginated `SUBMITTED` ledger claims |
| GET | `/dashboard/recent-activity` | `ledger,view` | Paginated ledger entries, most recently updated first |

**Business Rules & Logic:**
- **Tenant scoping**: Super Admin gets platform-wide figures; everyone else is scoped to their own tenant business.
- **`DashboardMetricService`** (shared, reused by Property/PropertyUnit/Ledger dashboards too — this explains why several "dashboards" exist across the API): builds count/sum/monthly-sum metric widgets with a trend comparison (current period vs. prior period), returning a consistent `{title, icon, value, trend}` shape for every widget. Handles divide-by-zero gracefully when there's no prior-period baseline.
- The four admin dashboard metrics: **Total Properties** (count), **Active Tenants** (renters with an active lease), **Pending Approvals** (ledger entries `SUBMITTED`), **Monthly Revenue** (sum of `PAID` entries this month vs. last month).

**Data Model:** Reads `Property`, `Renter`/`Lease`, `LedgerEntry` — owns no data of its own; a pure read/aggregation layer.

**Cross-Module Interactions:** Reads across Property, Lease/Renter, and Ledger domains. `DashboardMetricService` is directly reused by Property's, PropertyUnit's, and Ledger's own module-specific dashboard endpoints.

---

## Appendix: Cross-Cutting Concerns & Known Gaps

These are inconsistencies and gaps surfaced while reading the code, worth raising with engineering or scoping into future work. A 2026-08-01 audit pass (see `docs/CHANGES_2026-08-01_backend_audit.md` for full detail) fixed several items originally listed here; those are marked accordingly rather than removed, so the history stays visible.

- **Global uniqueness instead of tenant-scoped**: `Property.name` and `Amenity.slug` are still validated as globally unique across the whole table rather than per tenant business — two unrelated landlords still cannot both name a property "Building A." (`PropertyUnit.name` was fixed 2026-08-01 to be scoped per property instead of global.)
- **PlanController is unrouted**: subscription plans can only be managed via seeders/Tinker today; there is no API to list, create, or change a tenant business's plan after signup (`UpdateTenantBusinessRequest` doesn't even accept a `plan_uuid`).
- ~~`UpdateUserManagementRequest` has a shadowed `Status` import~~ — **fixed 2026-08-01**: this was a fatal error on every `PUT /user-management/{user}` request (a PHPUnit test class was imported instead of `App\Enum\Status`), not just an incorrect-validation risk.
- ~~TenantBusiness registration bypasses `TokenService`~~ — **fixed 2026-08-01**: `registerBusiness()` now issues tokens through `TokenService::issue()`, same as a normal login.
- ~~Amenity module doesn't use the `permission:{module},{action}` middleware~~ — **fixed 2026-08-01**: `store`/`update`/`destroy` are now gated the same way as other modules. (`destroy` previously had no role gating of any kind.)
- **Soft-deleted attachments lose their physical file**: `PropertyAttachmentService::delete()` removes the file from storage before soft-deleting the DB row, so a soft-deleted attachment can never be restored intact. (Not addressed — no "restore attachment" flow exists to make this observable today.)
- **Several enum values are defined but never produced by current logic**: `LeaseHistoryAction::ended`, `PropertyUnitStatus::full`, and `AuditModule::rentals`/`tenant_business` all exist in code but no service path currently writes them — worth confirming whether they're reserved for planned features or dead code.
- ~~`PropertyUnitPolicy` is missing `view`/`delete` methods~~ — **fixed 2026-08-01**, and it was worse than a fallback: this app's base `Controller` doesn't include Laravel's `AuthorizesRequests` trait at all, so `$this->authorize()` fatally errored on every `show`/`destroy` call rather than merely denying. The deeper issue this masked — `PropertyUnit` had **no tenant isolation whatsoever** (no `tenant_business_id` column, no scope) — is also fixed; see the PropertyUnit module section above.
- ~~No workflow guard on `MaintenanceRequest` status transitions~~ — **fixed 2026-08-01**: `resolved`/`cancelled` are now terminal states.
- **No plan-limit enforcement for user/staff creation** — `Plan` only caps `max_properties`/`max_units`; a tenant can create unlimited staff users regardless of plan tier, inconsistent with the Property/PropertyUnit pattern. (Newly noted 2026-08-01.)
- **Magic-link tokens aren't scoped to a specific ledger entry, and grant a full session** — an overdue-rent reminder's link, if forwarded or leaked, grants a normal multi-day authenticated session rather than just the ability to settle that one charge. Narrowing this needs a schema change (`ledger_entry_id` on `magic_link_tokens`). (Newly noted 2026-08-01.)
- **`PermissionPerUser` overrides replace role permissions rather than layering with them**, and have no explicit "deny" — once a user has any active override row, their role's other permissions are ignored entirely. May be intentional; worth confirming with product. (Newly noted 2026-08-01.)
- **Notification delivery is in-app/database only** — no real-time push (the code has a placeholder for a future websocket broadcast) and no email/SMS channel, unlike the separate magic-link email notification.
