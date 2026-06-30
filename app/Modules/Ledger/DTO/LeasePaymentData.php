<?php

namespace App\Modules\Ledger\DTO;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use App\Modules\RenterManagement\DTO\LeaseData;

class LeasePaymentData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $lease_id,
        public string $billing_period,
        public ?Carbon $due_date,
        public string $amount_due,
        public ?string $amount_paid,
        public ?string $paid_at,
        public ?string $reference_no,
        public ?string $payment_method,
        public ?string $proof_path,
        public string $status,
        public string $created_at,
        public string $updated_at,
        public ?string $deleted_at,

        public ?LeaseData $lease = null
    ) {
    }
}