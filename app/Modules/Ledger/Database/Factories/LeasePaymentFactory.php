<?php

namespace Modules\Ledger\Database\Factories;

use App\Enums\LeasePaymentEnum;
use App\Modules\Ledger\Models\LeasePayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class LeasePaymentFactory extends Factory
{

    protected $model = LeasePayment::class;



    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'billing_period' => now()->startOfMonth()->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(5)->toDateString(),
            'amount_due' => 1000.00,
            'amount_paid' => null,
            'paid_at' => null,
            'reference_no' => null,
            'payment_method' => null,
            'proof_path' => null,
            'status' => LeasePaymentEnum::PENDING->value,
        ];
    }

    /**
     * State for a successfully paid payment.
     */
    public function paid(string $method = 'bank_transfer'): self
    {
        return $this->state(function (array $attributes) use ($method) {
            return [
                'status' => LeasePaymentEnum::PAID->value,
                'amount_paid' => $attributes['amount_due'],
                'paid_at' => Carbon::parse($attributes['due_date'])->subDays(rand(0, 3)),
                'reference_no' => 'REF-' . strtoupper(Str::random(8)),
                'payment_method' => $method,
            ];
        });
    }

    /**
     * State for an overdue payment.
     */
    public function overdue(): self
    {
        return $this->state(fn() => ['status' => LeasePaymentEnum::OVERDUE->value]);
    }
}
