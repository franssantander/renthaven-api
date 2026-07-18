<?php

namespace Database\Seeders;

use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AuditLogSeeder extends Seeder
{
    /**
     * Seed a realistic audit trail across multiple tenants and roles, with
     * staggered timestamps so relative "time ago" display can be exercised
     * (minutes ago, hours ago, days ago).
     */
    public function run(): void
    {
        $admins = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->get();
        $staff  = User::whereHas('role', fn ($q) => $q->where('slug', 'staff'))->get();
        $renters = User::whereHas('role', fn ($q) => $q->where('slug', 'tenant'))->with('renterProfile')->get();

        if ($admins->isEmpty()) {
            $this->command->warn('No admin users found — skipping audit log seeding.');
            return;
        }

        $entries = [];

        foreach ($admins as $index => $admin) {
            $entries[] = [
                'user'        => $admin,
                'module'      => AuditModule::AUTH,
                'action'      => AuditAction::LOGIN_SUCCESS,
                'description' => "Successful login for {$admin->email}",
                'created_at'  => Carbon::now()->subMinutes(5 + $index * 7),
            ];
            $entries[] = [
                'user'        => $admin,
                'module'      => AuditModule::PROPERTY,
                'action'      => AuditAction::UPDATED,
                'description' => 'Updated property details',
                'created_at'  => Carbon::now()->subHours(3 + $index),
            ];
        }

        foreach ($staff as $index => $member) {
            $entries[] = [
                'user'        => $member,
                'module'      => AuditModule::MAINTENANCE_REQUEST,
                'action'      => AuditAction::CREATED,
                'description' => 'Logged a new maintenance request',
                'created_at'  => Carbon::now()->subHours(10 + $index * 2),
            ];
        }

        foreach ($renters as $index => $renterUser) {
            $entry = LedgerEntry::withoutGlobalScopes()
                ->where('renter_id', $renterUser->renterProfile?->id)
                ->first();

            $entries[] = [
                'user'        => $renterUser,
                'module'      => AuditModule::BILLING,
                'action'      => AuditAction::UPDATED,
                'description' => 'Renter submitted a payment claim for review',
                'auditable'   => $entry,
                'created_at'  => Carbon::now()->subDays(1 + $index)->subHours(2),
            ];
            $entries[] = [
                'user'        => $renterUser,
                'module'      => AuditModule::AUTH,
                'action'      => AuditAction::MAGIC_LINK_LOGIN_SUCCESS,
                'description' => "Signed in via magic link: {$renterUser->email}",
                'created_at'  => Carbon::now()->subDays(2 + $index),
            ];
        }

        foreach ($entries as $data) {
            $log = AuditLog::create([
                'user_id'            => $data['user']->id,
                'tenant_business_id' => $data['user']->tenant_business_id,
                'actor_email'        => $data['user']->email,
                'module'             => $data['module']->value,
                'action'             => $data['action']->value,
                'description'        => $data['description'],
                'auditable_type'     => isset($data['auditable']) ? $data['auditable']?->getMorphClass() : null,
                'auditable_id'       => isset($data['auditable']) ? $data['auditable']?->getKey() : null,
                'ip_address'         => fake()->ipv4(),
                'user_agent'         => fake()->userAgent(),
            ]);

            // created_at defaults to now via the DB; backdate it to stagger the trail.
            $log->created_at = $data['created_at'];
            $log->save();
        }

        $this->command->info('Seeded ' . count($entries) . ' audit log entries across tenants and roles.');
    }
}
