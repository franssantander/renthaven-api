<?php

namespace Database\Seeders;

use App\Modules\BillManagement\Models\Bill;
use App\Modules\TenantManagement\Models\Lease;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BillSeeder extends Seeder
{
    public function run(): void
    {
        $leases = Lease::with(['property', 'user'])->get();

        foreach ($leases as $lease) {
            if ($lease->is_active) {
                $this->generateActiveLeaseBills($lease);
            } else {
                $this->generateHistoricalBills($lease);
            }
        }
    }

    private function generateActiveLeaseBills(Lease $lease)
    {
        $issuedDate = Carbon::now()->subMonth()->startOfMonth();
        Bill::create([
            'lease_id'         => $lease->id,
            'property_id'      => $lease->property_id,
            'user_id'          => $lease->user_id,
            'portfolio_id'     => $lease->portfolio_id,
            'type'             => 'rent',
            'status'           => 'paid',
            'amount'           => rand(5000, 15000),
            'issued_date'      => $issuedDate,
            'due_date'         => $issuedDate->copy()->addDays(5),
            'description'      => 'Rent for ' . $issuedDate->format('F Y'),
            'payment_method'   => collect(['Bank Transfer', 'Gcash', 'Cash'])->random(),
            'payment_reference'=> 'PAY-' . strtoupper(Str::random(8)),
            'payment_date'     => $issuedDate->copy()->addDays(rand(0, 4)),
        ]);

        $statusRand = rand(0, 2);
        $status = ['unpaid', 'overdue', 'pending'][$statusRand];
        
        $currentIssued = Carbon::now()->startOfMonth();
        
        Bill::create([
            'lease_id'         => $lease->id,
            'property_id'      => $lease->property_id,
            'user_id'          => $lease->user_id,
            'portfolio_id'     => $lease->portfolio_id,
            'type'             => 'rent',
            'status'           => $status,
            'amount'           => rand(5000, 15000),
            'issued_date'      => $currentIssued,
            'due_date'         => $currentIssued->copy()->addDays(5),
            'description'      => 'Rent for ' . $currentIssued->format('F Y'),
            'payment_method'   => $status === 'pending' ? 'Gcash' : null,
            'payment_reference'=> $status === 'pending' ? 'REF-' . strtoupper(Str::random(8)) : null,
            'payment_date'     => $status === 'pending' ? Carbon::now() : null,
        ]);

        $types = ['water', 'electricity'];
        foreach ($types as $type) {
            if (rand(0, 1)) {
                Bill::create([
                    'lease_id'         => $lease->id,
                    'property_id'      => $lease->property_id,
                    'user_id'          => $lease->user_id,
                    'portfolio_id'     => $lease->portfolio_id,
                    'type'             => $type,
                    'status'           => 'unpaid',
                    'amount'           => rand(200, 2000),
                    'issued_date'      => Carbon::now()->subDays(rand(1, 10)),
                    'due_date'         => Carbon::now()->addDays(rand(5, 15)),
                    'description'      => ucfirst($type) . ' consumption for current period',
                    'payment_method'   => null,
                    'payment_reference'=> null,
                    'payment_date'     => null,
                ]);
            }
        }
    }

    private function generateHistoricalBills(Lease $lease)
    {
        for ($i = 1; $i <= 3; $i++) {
            $issued = Carbon::parse($lease->start_date)->addMonths($i);
            Bill::create([
                'lease_id'         => $lease->id,
                'property_id'      => $lease->property_id,
                'user_id'          => $lease->user_id,
                'portfolio_id'     => $lease->portfolio_id,
                'type'             => 'rent',
                'status'           => 'paid',
                'amount'           => rand(5000, 12000),
                'issued_date'      => $issued,
                'due_date'         => $issued->copy()->addDays(5),
                'description'      => 'Historical rent payment',
                'payment_method'   => 'Bank Transfer',
                'payment_reference'=> 'HIST-' . strtoupper(Str::random(8)),
                'payment_date'     => $issued->copy()->addDays(rand(0, 3)),
            ]);
        }
    }
}