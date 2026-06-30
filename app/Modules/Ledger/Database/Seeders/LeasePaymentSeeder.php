<?php

namespace Modules\Ledger\Database\Seeders;

use App\Modules\Ledger\Models\LeasePayment;
use App\Modules\Property\Models\Property;
use App\Modules\RenterManagement\Models\Lease;
use App\Modules\RenterManagement\Models\RenterUser;
use App\Modules\Tenant\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeasePaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $properties = Property::where('tenant_id', $tenant->id)->get();
            $renters = RenterUser::where('tenant_id', $tenant->id)->get();

            if ($properties->isEmpty() || $renters->isEmpty()) {
                continue;
            }

            $renterPool = $renters->shuffle();

            foreach ($properties as $property) {
                $maxCapacity = $property->is_shared ? $property->pax : $property->total_units;
                $occupancyCount = rand(0, $maxCapacity);

                for ($i = 1; $i <= $occupancyCount; $i++) {
                    if ($renterPool->isEmpty()) {
                        break;
                    }

                    $renter = $renterPool->pop();
                    $prefix = $property->is_shared ? 'Bed' : 'Unit';

                    // Determine lease start dates to be a few months in the past
                    // so we have a realistic payment ledger history!
                    $startDate = Carbon::now()->subMonths(rand(2, 4))->startOfMonth();

                    $factory = (rand(1, 10) <= 3)
                        ? Lease::factory()->fixed()
                        : Lease::factory();

                    $lease = $factory->create([
                        'renter_user_id' => $renter->id,
                        'property_id' => $property->id,
                        'tenant_id' => $tenant->id,
                        'monthly_rent' => $property->monthly_rent_price,
                        'unit_number' => "{$prefix} {$i}",
                        'start_date' => $startDate->toDateString(),
                        'end_date' => (rand(1, 10) <= 3) ? $startDate->copy()->addYear()->toDateString() : null,
                        'is_active' => true,
                    ]);

                    // --- GENERATE CHRONOLOGICAL PAYMENTS FOR THIS LEASE ---
                    $this->seedPaymentsForLease($lease);
                }

                $currentActiveCount = Lease::where('property_id', $property->id)
                    ->where('is_active', true)
                    ->count();

                $property->update([
                    'occupied' => $currentActiveCount,
                    'is_available' => $currentActiveCount < $maxCapacity
                ]);
            }
        }

        $this->command->info('Leases and historic Ledger Payments seeded perfectly!');
    }

    /**
     * Helper to generate historical and current month records
     */
    private function seedPaymentsForLease(Lease $lease): void
    {
        $currentPeriod = Carbon::parse($lease->start_date)->startOfMonth();
        $today = Carbon::now();

        while ($currentPeriod->getTimestamp() <= $today->getTimestamp()) {
            $dueDate = $currentPeriod->copy()->addDays(5); // Due on the 5th of every month

            $paymentFactory = LeasePayment::factory()->state([
                'lease_id' => $lease->id,
                'billing_period' => $currentPeriod->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'amount_due' => $lease->monthly_rent,
            ]);

            // If the period is in the past, it should mostly be paid
            if ($dueDate->isPast()) {
                // 90% chance it was paid on time, 10% chance left overdue
                if (rand(1, 10) <= 9) {
                    $paymentFactory->paid(collect(['gcash', 'bank_transfer', 'cash'])->random())->create();
                } else {
                    $paymentFactory->overdue()->create();
                }
            } else {
                // Current month billing is pending
                $paymentFactory->create();
            }

            // Move forward 1 month
            $currentPeriod->addMonth();
        }
    }
}
