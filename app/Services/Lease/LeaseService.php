<?php

namespace App\Services\Lease;

use App\Enum\LeaseHistoryAction;
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
        string $startDate,
        ?string $endDate,
        int $tenantBusinessId,
        ?int $performedBy = null,
    ): array {
        $leases = DB::transaction(function () use ($propertyUnit, $tenants, $startDate, $endDate, $tenantBusinessId, $performedBy) {
            $created = [];

            foreach ($tenants as $tenantInput) {
                $user = !empty($tenantInput['uuid'])
                    ? User::where('uuid', $tenantInput['uuid'])
                        ->where('tenant_business_id', $tenantBusinessId)
                        ->firstOrFail()
                    : $this->findOrCreateUser($tenantInput, $tenantBusinessId);

                $renter = $this->findOrCreateRenter($user, $tenantBusinessId);

                $lease = Lease::create([
                    'property_unit_id' => $propertyUnit->id,
                    'renter_id'        => $renter->id,
                    'start_date'       => $startDate,
                    'end_date'         => $endDate,
                    'is_active'        => true,
                ]);

                LeaseHistory::create([
                    'lease_id'              => $lease->id,
                    'previous_lease_id'     => null,
                    'renter_id'             => $renter->id,
                    'from_property_unit_id' => null,
                    'to_property_unit_id'   => $propertyUnit->id,
                    'action'                => LeaseHistoryAction::ASSIGNED,
                    'effective_date'        => $startDate,
                    'performed_by'          => $performedBy,
                ]);

                $created[] = $lease;
            }

            return $created;
        });

        $this->recalculateUnitStatus($propertyUnit);

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

            $lease->update([
                'end_date'  => $moveDate,
                'is_active' => false,
            ]);

            $newLease = Lease::create([
                'property_unit_id' => $newPropertyUnit->id,
                'renter_id'        => $lease->renter_id,
                'start_date'       => $moveDate,
                'end_date'         => null,
                'is_active'        => true,
            ]);

            LeaseHistory::create([
                'lease_id'              => $newLease->id,
                'previous_lease_id'     => $lease->id,
                'renter_id'             => $lease->renter_id,
                'from_property_unit_id' => $previousPropertyUnit?->id,
                'to_property_unit_id'   => $newPropertyUnit->id,
                'action'                => LeaseHistoryAction::REASSIGNED,
                'effective_date'        => $moveDate,
                'performed_by'          => $performedBy,
            ]);

            return $newLease;
        });

        if ($lease->propertyUnit) {
            $this->recalculateUnitStatus($lease->propertyUnit);
        }

        $this->recalculateUnitStatus($newPropertyUnit);

        return $newLease;
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
            'role_id'            => $tenantRoleId,
            'tenant_business_id' => $tenantBusinessId,
            'full_name'          => "{$tenantInput['first_name']} {$tenantInput['last_name']}",
            'email'              => $tenantInput['email'],
            'phone'              => $tenantInput['phone'] ?? null,
            'username'           => Str::slug($tenantInput['email']),
            'password'           => Hash::make(Str::random(32)),
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
                'first_name' => Str::before($user->full_name, ' '),
                'last_name'  => Str::after($user->full_name, ' ') ?: $user->full_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
            ]
        );
    }
}
