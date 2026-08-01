# Backend Audit & Fixes — 2026-08-01

A full pass over every backend module (Property, PropertyUnit, Lease, Ledger/payments,
MaintenanceRequest, Permission/Auth/UserManagement/TenantBusiness, AuditLog/Notification/
Dashboard/Amenity/RenterPortal) against the conventions in `CLAUDE.md`, looking for bugs and
misalignments in the day-to-day rental-management flow. This document records what was found
and what was changed. See `docs/BUSINESS_FLOWS.md` for the current (post-fix) narrative
description of each module — several "Known Gaps" it previously listed are resolved by this
pass and have been updated there.

Verification performed: `php -l` on every changed file, a Pint pass scoped to the changed files,
the full `php artisan test` suite, `php artisan migrate:fresh --seed` end-to-end, and a live
Tinker reproduction of the PropertyUnit cross-tenant IDOR (confirmed exploitable before the fix,
confirmed blocked after).

---

## Critical: security / data-isolation

### 1. Privilege escalation via `role_uuid`
**Where:** `StoreUserManagementRequest`, `UpdateUserManagementRequest`
**Problem:** `role_uuid` only validated that the role existed — any admin/staff user could create or
update a user (including themselves) with the `super_admin` role, which bypasses tenant scoping
entirely (`BelongsToTenantBusiness` exempts `super_admin`).
**Fix:** `role_uuid`'s `exists` rule now excludes the `super_admin` role unless the acting user is
already a super admin.

### 2. Cross-tenant permission tampering
**Where:** `PermissionController::sync`, `PermissionController::revokeAll`
**Problem:** Unlike `show()`, these had no tenant-ownership check on the target user at all, and
resolved the target via `UuidResolver` (a raw, unscoped DB lookup) — any user with
`permission_management,update` could rewrite another tenant's users' permission overrides.
**Fix:** Added `assertCanManagePermissionsFor()`, mirroring the check already used in `show()`;
non-super-admins are now blocked (404) from acting on a user outside their own tenant business.

### 3. Cross-tenant IDOR on property units (no tenant isolation at all)
**Where:** `PropertyUnit` model / `PropertyUnitController`
**Problem:** `property_units` has no `tenant_business_id` column and the model didn't use
`BelongsToTenantBusiness`. Route-model-binding resolved any unit by UUID with zero tenant
filtering. `update`, `storeAttachments`, and `destroyAttachment` had **no tenant check
whatsoever** — any authenticated user (in any tenant) with `properties,update`/`create`
permission could edit/upload to/delete another tenant's unit by guessing or enumerating its UUID.
**Fix:** Added a dedicated global scope to `PropertyUnit` (mirroring `BelongsToTenantBusiness`'s
semantics via the `property` relation, since there's no direct `tenant_business_id` column) —
super admins remain unscoped, everyone else is confined to their own tenant automatically on
every query, including route-model-binding. Verified live via Tinker: a cross-tenant unit is now
unreachable by UUID for a regular admin and still reachable for a super admin.

### 4. Two endpoints were completely broken (500 on every request)
**Where:** `PropertyUnitController::show`, `PropertyUnitController::destroy`
**Problem:** Both called `$this->authorize(...)`, a method from Laravel's `AuthorizesRequests`
trait — which this app's base `Controller` never included. Every call to these endpoints threw
"call to undefined method." Even had the trait existed, `PropertyUnitPolicy` never defined
`view`/`delete` methods, so the calls would have always denied (403) for every user, including
legitimate ones.
**Fix:** Removed the two non-functional `$this->authorize()` calls. Authorization for these
actions is now correctly provided by the route's `permission:properties,{view|delete}`
middleware plus the new tenant-scoping global scope (#3) — matching how every other CRUD action
in this controller is already authorized.

### 5. Payment race condition (potential double-payment)
**Where:** `LedgerService::markPaid` / `applyPayment`, `submitPaymentClaim`
**Problem:** Read-modify-write on `amount_paid`/`status` with no row lock or transaction. Two
concurrent requests (e.g. an admin's `markPaid` racing a renter's `submitPayment` approval) could
both read the same stale balance and both write, silently clobbering one payment and consuming
two OR numbers for one paid entry.
**Fix:** Both methods now run inside `DB::transaction()` with `lockForUpdate()` on the ledger
entry row for the duration of the read-modify-write cycle. `submitPaymentClaim` also re-checks
the entry's status under the lock rather than trusting a pre-transaction check.

### 6. Silent overpayment clamping
**Where:** `MarkPaidRequest`, `SubmitPaymentRequest`
**Problem:** An `amount` greater than the entry's outstanding balance was silently clamped by
`applyPayment()` and the excess simply discarded — no error, no record of the difference. A
data-entry typo (extra digit) would be masked rather than caught.
**Fix:** Both requests now reject (422) an `amount` that exceeds the entry's current `balance`.

### 7. Ledger/MaintenanceRequest dashboards were empty for super admins
**Where:** `LedgerEntry`, `MaintenanceRequest` models; their controllers' `dashboard`/`index`/
`show`/`markPaid`/`rejectPayment`/`updateStatus` methods
**Problem:** These models have a `tenant_business_id` column but never used
`BelongsToTenantBusiness`; every controller method manually filtered by
`$request->user()->tenant_business_id` with no super-admin bypass — a super admin (whose
`tenant_business_id` is null) got empty results or a spurious 404 everywhere.
**Fix:** Both models now use `BelongsToTenantBusiness` directly (they have the column, unlike
PropertyUnit). Removed the now-redundant/broken manual tenant checks across both controllers.

### 8. `PropertyController`/`PropertyUnitController::syncAmenities` broke for super admins
**Where:** Both controllers' `syncAmenities`
**Problem:** `abort_unless($x->tenant_business_id === $request->user()->tenant_business_id, 404)`
re-imposed strict equality against the *acting user's own* tenant — for a super admin
(`tenant_business_id` null/different) this always 404'd, even though the model's own global scope
already legitimately allowed the fetch. Contradicts "super admin sees everything, unscoped."
**Fix:** Removed the redundant/broken manual checks; the models' own tenant scopes are
authoritative.

---

## High: functional bugs

### 9. Fatal error on every `PUT /user-management/{user}` request
**Where:** `UpdateUserManagementRequest`
**Problem:** `use PHPUnit\Logging\OpenTestReporting\Status;` shadowed the intended
`App\Enum\Status` in the `Rule::enum()` call — a PHPUnit (dev-only) class used at runtime.
**Fix:** Corrected the import to `App\Enum\Status`.

### 10. `PropertyUnit` update was a silent no-op
**Where:** `UpdatePropertyUnitRequest`
**Problem:** `rules()` returned `[]`, so `$request->validated()` was always empty — the endpoint
returned "Unit updated successfully" (200) without ever changing anything.
**Fix:** Added real rules for `name` (uniqueness scoped to `property_id`, excluding soft-deleted
rows and the current record), `capacity` (`min:1`), `rent_price`, and `status`.

### 11. `PropertyUnit` unit-name uniqueness was global, not per-property
**Where:** `StorePropertyUnitRequest`
**Problem:** `unique:property_units,name` was a table-wide constraint — once any tenant created a
unit named "Unit 1," no other tenant/property could ever reuse that name. Also didn't exclude
soft-deleted rows.
**Fix:** Scoped uniqueness to `property_id`, excluding soft-deleted rows. Also raised
`capacity`'s minimum from `0` to `1` (a unit that houses zero tenants doesn't make sense).

### 12. `MaintenanceRequest` had no status-transition guard
**Where:** `MaintenanceRequestService::updateStatus`
**Problem:** A request already `resolved` or `cancelled` could be silently moved back to
`open`/`in_progress` with no error.
**Fix:** `resolved`/`cancelled` are now treated as terminal; attempting to transition out of them
returns 422.

### 13. `MaintenanceRequest` assignment could target a user from another tenant
**Where:** `MaintenanceRequestController::updateStatus`
**Problem:** `assigned_to_uuid` was resolved via the unscoped `UuidResolver::id('users', ...)` —
no check that the resolved user belonged to the same tenant business as the request.
**Fix:** Resolution now filters by the maintenance request's own `tenant_business_id` and
rejects (422) if no matching user is found.

### 14. `Lease` transaction boundary didn't cover unit-status recalculation
**Where:** `LeaseService::assignTenants`, `reassignUnit`, `terminateLease`
**Problem:** `recalculateUnitStatus()` was called *after* the `DB::transaction()` closure
returned — a crash between commit and that call could leave `property_units.status` stale (e.g.
a unit stays `available` after a lease is created).
**Fix:** Moved the recalculation call inside each transaction.

### 15. `Lease` capacity check had a TOCTOU race
**Where:** `LeaseService::assignTenants`
**Problem:** The `Gate::inspect` capacity check runs before the transaction opens; two concurrent
requests could both pass it and jointly exceed the unit's capacity.
**Fix:** Added a row-locked re-check of active-lease count vs. capacity inside the transaction,
as a second authoritative gate.

### 16. No guard against a renter having two simultaneous active leases
**Where:** `LeaseService::assignTenants`
**Fix:** Added an explicit check against `Renter::activeLease()` before creating a new lease for
that renter.

### 17. Reassignment/termination could produce a negative-length lease
**Where:** `LeaseService::reassignUnit`, `terminateLease`
**Problem:** No validation that a reassignment's `move_date` preceded the original lease's
`end_date`, or that a termination's `move_out_date` was on/after the lease's `start_date`.
**Fix:** Both now reject (422) dates that would produce a lease with `end_date < start_date`.

### 18. Zero-length fixed-term lease allowed
**Where:** `StoreLeaseRequest`
**Fix:** `end_date` rule changed from `after_or_equal:start_date` to `after:start_date`.

### 19. `registerBusiness` bypassed the shared `TokenService`
**Where:** `TenantBusinessController::registerBusiness`
**Problem:** Issued a raw Passport token directly instead of `TokenService::issue()`, so a
newly-registered user got no `RefreshToken` row — `refresh`/`logout` would fail for that first
session, unlike a normal login.
**Fix:** Now uses `TokenService::issue()`, consistent with `AuthController::login`.

### 20. `TenantBusinessData::$buusiness_address` typo
**Where:** `TenantBusinessData`
**Problem:** Misspelled property meant `business_address` never serialized into any API response
using this Data class.
**Fix:** Renamed to `business_address`. Also removed an unused, non-existent-in-production
`phpDocumentor\Reflection\Types\Boolean` import from the same file.

### 21. `UpdateTenantBusinessRequest` missing `->ignore()` on unique rules
**Where:** `UpdateTenantBusinessRequest`
**Problem:** `email`/`phone`/`tin` uniqueness wasn't scoped to ignore the record being updated —
submitting a business's own unchanged email/phone/tin failed validation.
**Fix:** Added `Rule::unique(...)->ignore($tenantBusinessId)` to all three.

---

## Medium: missing route-level authorization

Several mutating routes had no `permission:{module},{action}` middleware at all, relying solely
on `auth:api` (or, for Amenity, a role check baked into the FormRequest with no way to grant/deny
per the permission matrix):

- `routes/v1/properties.php`: `index`, `store`, `update`, `syncAmenities`, `destroy`, `dashboard`,
  `show` — added `permission:properties,{view|create|update|delete}` to each.
- `routes/v1/amenities.php`: `store`, `update`, `destroy` — added
  `permission:properties,{create|update|delete}`. (`destroy` previously had **no role gating of
  any kind** — any authenticated user, including a renter, could delete an amenity.)
- `MaintenanceRequestController::store` (staff/non-tenant branch only — the route itself must stay
  open since renters also file through it) — added an explicit
  `hasPermission('maintenance', 'create')` check for non-renter callers.
- `MaintenanceRequestController::show` — replaced the broken
  `$maintenanceRequest->tenant_business_id === $user->tenant_business_id` check (broke for super
  admins) with renter-ownership OR `hasPermission('maintenance', 'view')`.

## Consistency: Data classes instead of raw Eloquent models

Per `CLAUDE.md` ("controllers do not return raw Eloquent models"), the following actions were
returning bare models/arrays and now return the corresponding `*Data` class:
`PropertyController` (store/show/update/syncAmenities/storeAttachments), `PropertyUnitController`
(store/show/update/syncAmenities/storeAttachments), `LeaseController` (store/update/renew/destroy),
`LedgerController` (markPaid/submitPayment/rejectPayment), `MaintenanceRequestController`
(store/show/updateStatus), `AmenityController` (index/store/update). `LeaseData` was also missing
a `renter` field entirely (lease responses had no renter identity) — added.

## Minor: audit-log completeness, validation messages

- `PropertyController::destroy`, `PropertyUnitController::destroy` — the delete audit-log call
  omitted `auditable:`, so the log row's polymorphic link (`auditable_type`/`auditable_id`) was
  always null on delete, unlike every other action in the same controllers. Fixed.
- Added `messages()` (custom validation text, per `CLAUDE.md` convention) to ~20 Form Requests
  that were missing it: Property (Store/Update/SyncAmenities/StoreAttachment), PropertyUnit
  (Store/Update/SyncAmenities/StoreAttachment), Lease (Store/Update), Ledger (MarkPaid),
  MaintenanceRequest (Store/UpdateStatus), Amenity (Store/Update), UserManagement (Store/Update),
  TenantBusiness (Update).

---

## Not fixed — flagged for a follow-up decision

These are real gaps but are either product/architecture decisions or larger schema changes,
outside the scope of a bug-fix pass:

- **Magic-link tokens aren't scoped to a specific ledger entry, and grant a full session.**
  `MagicLinkToken` is keyed only to a user, not a charge — a reminder email's link, if forwarded
  or leaked, grants a normal 7/30-day authenticated session (full ledger/lease/dashboard access),
  not just the ability to settle that one overdue charge. Narrowing this would need a schema
  change (`ledger_entry_id` on `magic_link_tokens`) and a scoped-token verification path.
- **`PermissionPerUser` overrides replace role permissions rather than layering with them, and
  have no explicit "deny."** Once a user has *any* active override row, their role's other
  permissions are ignored entirely rather than augmented — and there's no way to express "grant
  everything from the role except X." This may be intentional per how `CLAUDE.md` describes
  overrides, but is worth confirming with product.
- **No plan-limit enforcement for user/staff creation.** `Plan` only has `max_properties`/
  `max_units`; there's no `UserPolicy::create()` equivalent, so a tenant can create unlimited
  staff users regardless of plan tier — inconsistent with the Property/PropertyUnit pattern.
- **`PropertyManagementSeeder` only seeds one tenant business's properties/units.** Multi-tenant
  scenarios (including the cross-tenant checks fixed above) aren't exercised by
  `migrate:fresh --seed` out of the box; verifying tenant isolation currently requires manually
  creating a second tenant's data (as done for this audit's verification).
- **`Property.name` and `Amenity.slug` are still globally unique**, not scoped per tenant
  business (same class of bug as the `PropertyUnit.name` issue fixed above, #11) — left alone
  since fixing it changes user-visible validation behavior on two more resources; flagging for a
  deliberate decision rather than bundling into this pass.
- **`PropertyPolicy`'s `view`/`update`/`delete`/`viewAny` methods all hard-return `false`** and
  are unused dead code (never called by any controller — those actions are authorized via route
  `permission:` middleware instead). Harmless as-is; worth removing or implementing for real if
  object-level Property policies are ever needed.
- **`PropertyAttachmentService::delete()` removes the physical file before soft-deleting the DB
  row**, so a soft-deleted attachment can never be restored intact. Not touched — no "restore
  attachment" flow exists today to make this observable, but worth fixing if one is added.
- **`LedgerService`'s full-table-scan `MagicLinkToken::consume()`** and lack of throttling on
  `/auth/magic-link*` — functionally correct today, a scaling/hardening concern rather than a
  correctness bug.
