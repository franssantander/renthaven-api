<?php

namespace Database\Seeders;

use App\Modules\BillManagement\Models\Bill;
use App\Modules\TenantManagement\Models\Lease;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BillSeeder extends Seeder
{
    public function run(): void
    {
        $leases = Lease::with(['property', 'user'])->get();

        foreach ($leases as $lease) {
            if ($lease->is_active) {
                // Scenario A: Active Leases (Current Rent + Utilities)
                $this->generateActiveLeaseBills($lease);
            } else {
                // Scenario B: Historical Leases (Past records, all paid)
                $this->generateHistoricalBills($lease);
            }
        }
    }

    private function generateActiveLeaseBills(Lease $lease)
    {
        // 1. Create a "Paid" Rent bill for last month
        Bill::create([
            'lease_id'     => $lease->id,
            'property_id'  => $lease->property_id,
            'user_id'      => $lease->user_id,
            'portfolio_id' => $lease->portfolio_id,
            'type'         => 'rent',
            'status'       => 'paid',
            'amount'       => rand(5000, 15000),
            'issued_date'  => Carbon::now()->subMonth()->startOfMonth(),
            'due_date'     => Carbon::now()->subMonth()->startOfMonth()->addDays(5),
            'description'  => 'Rent for ' . Carbon::now()->subMonth()->format('F Y'),
        ]);

        // 2. Create a "Current" Rent bill (Unpaid or Overdue)
        $isOverdue = rand(0, 1);
        Bill::create([
            'lease_id'     => $lease->id,
            'property_id'  => $lease->property_id,
            'user_id'      => $lease->user_id,
            'portfolio_id' => $lease->portfolio_id,
            'type'         => 'rent',
            'status'       => $isOverdue ? 'overdue' : 'unpaid',
            'amount'       => rand(5000, 15000),
            'issued_date'  => Carbon::now()->startOfMonth(),
            'due_date'     => Carbon::now()->startOfMonth()->addDays(5),
            'description'  => 'Rent for ' . Carbon::now()->format('F Y'),
        ]);

        // 3. Random Utility Bills (Water/Electricity)
        $types = ['water', 'electricity'];
        foreach ($types as $type) {
            if (rand(0, 1)) {
                Bill::create([
                    'lease_id'     => $lease->id,
                    'property_id'  => $lease->property_id,
                    'user_id'      => $lease->user_id,
                    'portfolio_id' => $lease->portfolio_id,
                    'type'         => $type,
                    'status'       => 'unpaid',
                    'amount'       => rand(200, 2000),
                    'issued_date'  => Carbon::now()->subDays(rand(1, 10)),
                    'due_date'     => Carbon::now()->addDays(rand(5, 15)),
                    'description'  => ucfirst($type) . ' consumption for current period',
                ]);
            }
        }
    }

    private function generateHistoricalBills(Lease $lease)
    {
        // For inactive leases, create 2-3 historical paid rent bills
        for ($i = 1; $i <= 3; $i++) {
            Bill::create([
                'lease_id'     => $lease->id,
                'property_id'  => $lease->property_id,
                'user_id'      => $lease->user_id,
                'portfolio_id' => $lease->portfolio_id,
                'type'         => 'rent',
                'status'       => 'paid',
                'amount'       => rand(5000, 12000),
                'issued_date'  => Carbon::parse($lease->start_date)->addMonths($i),
                'due_date'     => Carbon::parse($lease->start_date)->addMonths($i)->addDays(5),
                'description'  => 'Historical rent payment',
            ]);
        }
    }
}