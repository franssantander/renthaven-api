<?php

namespace App\Services\Lease;

use App\Enum\DepositStatus;
use App\Enum\LeaseHistoryAction;
use App\Enum\LeaseTermType;
use App\Enum\PropertyUnitStatus;
use App\Enum\Role as RoleEnum;
use App\Models\Lease;
use App\Models\LeaseHistory;
use App\Models\PropertyUnit;
use App\Models\Renter;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class LeaseService
{
    /**
     * Assign one or more tenants to a property unit.
     *
     * Each entry in $tenants is either:
     *  - ['uuid' => '...'] referencing an existing user, or
     *  - ['first_name' => ..., 'last_name' => ..., 'email' => ..., 'phone' => ...] for a brand-new tenant,
     *    for whom a User account is created (unverified, random password + a set-password email).
     *
     * @param  array<int, array<string, mixed>>  $tenants
     * @return Lease[]
     */
    public function assignTenants(
        PropertyUnit $propertyUnit,
        array $tenants,
        LeaseTermType $termType,
        string $startDate,
        ?string $endDate,
        int $tenantBusinessId,
        ?int $performedBy = null,
    ): array {
        $leases = DB::transaction(function () use ($propertyUnit, $tenants, $termType, $startDate, $endDate, $tenantBusinessId, $performedBy) {
            // Re-check capacity under a row lock: the caller's Gate::inspect check
            // happens before this transaction opens, so it can't by itself stop two
            // concurrent requests from both passing and over-filling the unit.
            $propertyUnit->refresh();
            DB::table('property_units')->where('id', $propertyUnit->id)->lockForUpdate()->value('id');

            $activeCount = Lease::where('property_unit_id', $propertyUnit->id)->where('is_active', true)->count();
            abort_if(
                $activeCount + count($tenants) > $propertyUnit->capacity,
                422,
                "This unit only has room for {$propertyUnit->capacity} tenant(s); {$activeCount} already active."
            );

            $created = [];

            foreach ($tenants as $tenantInput) {
                $user = ! empty($tenantInput['uuid'])
                    ? User::where('uuid', $tenantInput['uuid'])
                        ->where('tenant_business_id', $tenantBusinessId)
                        ->firstOrFail()
                    : $this->findOrCreateUser($tenantInput, $tenantBusinessId);

                $renter = $this->findOrCreateRenter($user, $tenantBusinessId);

                abort_if(
                    $renter->activeLease()->exists(),
                    422,
                    "{$renter->first_name} {$renter->last_name} already has an active lease."
                );

                $lease = Lease::create([
                    'property_unit_id' => $propertyUnit->id,
                    'renter_id' => $renter->id,
                    'term_type' => $termType,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_active' => true,
                    'security_deposit' => $tenantInput['security_deposit'] ?? 0,
                    'advance_rent' => $tenantInput['advance_rent'] ?? 0,
                ]);

                LeaseHistory::create([
                    'lease_id' => $lease->id,
                    'previous_lease_id' => null,
                    'renter_id' => $renter->id,
                    'from_property_unit_id' => null,
                    'to_property_unit_id' => $propertyUnit->id,
                    'action' => LeaseHistoryAction::ASSIGNED,
                    'effective_date' => $startDate,
                    'performed_by' => $performedBy,
                ]);

                $created[] = $lease;
            }

            $this->recalculateUnitStatus($propertyUnit);

            return $created;
        });

        return $leases;
    }

    /**
     * Reassign a tenant to a different property unit: ends the current lease
     * and starts a new one on the new unit, preserving a timeline of both
     * units' occupancy history via LeaseHistory.
     */
    public function reassignUnit(
        Lease $lease,
        PropertyUnit $newPropertyUnit,
        ?string $moveDate = null,
        ?int $performedBy = null,
    ): Lease {
        $moveDate ??= Carbon::now()->toDateString();

        $newLease = DB::transaction(function () use ($lease, $newPropertyUnit, $moveDate, $performedBy) {
            $previousPropertyUnit = $lease->propertyUnit;
            $originalEndDate = $lease->end_date;

            if ($lease->term_type === LeaseTermType::FIXED_TERM && $originalEndDate && Carbon::parse($moveDate)->gte($originalEndDate)) {
                abort(422, 'The move date must be before the lease\'s current end date.');
            }

            $lease->update([
                'end_date' => $moveDate,
                'is_active' => false,
            ]);

            $newLease = Lease::create([
                'property_unit_id' => $newPropertyUnit->id,
                'renter_id' => $lease->renter_id,
                'term_type' => $lease->term_type,
                'start_date' => $moveDate,
                // A fixed-term lease keeps its original end date after a move; a monthly lease has none.
                'end_date' => $lease->term_type === LeaseTermType::FIXED_TERM ? $originalEndDate : null,
                'is_active' => true,
            ]);

            LeaseHistory::create([
                'lease_id' => $newLease->id,
                'previous_lease_id' => $lease->id,
                'renter_id' => $lease->renter_id,
                'from_property_unit_id' => $previousPropertyUnit?->id,
                'to_property_unit_id' => $newPropertyUnit->id,
                'action' => LeaseHistoryAction::REASSIGNED,
                'effective_date' => $moveDate,
                'performed_by' => $performedBy,
            ]);

            if ($previousPropertyUnit) {
                $this->recalculateUnitStatus($previousPropertyUnit);
            }

            $this->recalculateUnitStatus($newPropertyUnit);

            return $newLease;
        });

        return $newLease;
    }

    /**
     * Terminate a lease permanently (the tenant is moving out with no
     * replacement unit) and settle the security deposit.
     *
     * @param  array<int, array{description: string, amount: float}>  $deductions
     */
    public function terminateLease(
        Lease $lease,
        string $moveOutDate,
        array $deductions = [],
        ?string $notes = null,
        ?int $performedBy = null,
    ): Lease {
        DB::transaction(function () use ($lease, $moveOutDate, $deductions, $notes, $performedBy) {
            $propertyUnit = $lease->propertyUnit;

            if (Carbon::parse($moveOutDate)->lt($lease->start_date)) {
                abort(422, 'The move-out date cannot be before the lease\'s start date.');
            }

            $totalDeductions = array_sum(array_column($deductions, 'amount'));
            $refundAmount = round((float) $lease->security_deposit - $totalDeductions, 2);

            $depositStatus = match (true) {
                $refundAmount <= 0 && $totalDeductions > 0 => DepositStatus::FORFEITED,
                $totalDeductions > 0 => DepositStatus::PARTIALLY_REFUNDED,
                default => DepositStatus::REFUNDED,
            };

            $lease->update([
                'end_date' => $moveOutDate,
                'is_active' => false,
                'deposit_deductions' => $deductions,
                'deposit_refunded_amount' => max($refundAmount, 0),
                'deposit_refunded_at' => now(),
                'deposit_status' => $depositStatus,
            ]);

            LeaseHistory::create([
                'lease_id' => $lease->id,
                'previous_lease_id' => null,
                'renter_id' => $lease->renter_id,
                'from_property_unit_id' => $propertyUnit?->id,
                'to_property_unit_id' => null,
                'action' => LeaseHistoryAction::ENDED,
                'effective_date' => $moveOutDate,
                'performed_by' => $performedBy,
                'notes' => $notes,
            ]);

            if ($propertyUnit) {
                $this->recalculateUnitStatus($propertyUnit);
            }
        });

        return $lease->fresh();
    }

    /**
     * Renew a fixed-term lease by extending its end date. Monthly leases are
     * already open-ended and have nothing to renew.
     */
    public function renewLease(Lease $lease, string $newEndDate, ?int $performedBy = null): Lease
    {
        $oldEndDate = $lease->end_date?->toDateString();

        DB::transaction(function () use ($lease, $newEndDate, $performedBy, $oldEndDate) {
            $lease->update(['end_date' => $newEndDate]);

            LeaseHistory::create([
                'lease_id' => $lease->id,
                'previous_lease_id' => null,
                'renter_id' => $lease->renter_id,
                'from_property_unit_id' => $lease->property_unit_id,
                'to_property_unit_id' => $lease->property_unit_id,
                'action' => LeaseHistoryAction::RENEWED,
                'effective_date' => $newEndDate,
                'performed_by' => $performedBy,
                'notes' => "Renewed from {$oldEndDate} to {$newEndDate}",
            ]);
        });

        return $lease->fresh();
    }

    /**
     * Recompute and persist a property unit's occupancy status based on its
     * current count of active leases versus its capacity.
     */
    public function recalculateUnitStatus(PropertyUnit $propertyUnit): void
    {
        $activeCount = Lease::where('property_unit_id', $propertyUnit->id)
            ->where('is_active', true)
            ->count();

        $status = match (true) {
            $activeCount <= 0 => PropertyUnitStatus::AVAILABLE,
            $activeCount >= $propertyUnit->capacity => PropertyUnitStatus::OCCUPIED,
            default => PropertyUnitStatus::PARTIALLY_OCCUPIED,
        };

        if ($propertyUnit->status !== $status) {
            $propertyUnit->update(['status' => $status]);
        }
    }

    /**
     * Find an existing user by email, or create a new unverified tenant user
     * with a random password and notify them to set their own.
     *
     * @param  array<string, mixed>  $tenantInput
     */
    protected function findOrCreateUser(array $tenantInput, int $tenantBusinessId): User
    {
        $user = User::where('email', $tenantInput['email'])->first();

        if ($user) {
            abort_unless($user->tenant_business_id === $tenantBusinessId, 422, "The email {$tenantInput['email']} is already registered to another business.");

            return $user;
        }

        $tenantRoleId = Role::where('slug', RoleEnum::TENANT->value)->firstOrFail()->id;

        $user = User::create([
            'role_id' => $tenantRoleId,
            'tenant_business_id' => $tenantBusinessId,
            'first_name' => $tenantInput['first_name'],
            'last_name' => $tenantInput['last_name'],
            'email' => $tenantInput['email'],
            'phone' => $tenantInput['phone'] ?? null,
            'username' => Str::slug($tenantInput['email']),
            'password' => Hash::make(Str::random(32)),
        ]);

        $token = Password::createToken($user);
        $user->notify(new SetPasswordNotification($token));

        return $user;
    }

    /**
     * Find or create the Renter profile linking a user to a tenant business.
     */
    protected function findOrCreateRenter(User $user, int $tenantBusinessId): Renter
    {
        return Renter::firstOrCreate(
            ['user_id' => $user->id, 'tenant_business_id' => $tenantBusinessId],
            [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]
        );
    }
}
