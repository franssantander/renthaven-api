<?php

use App\Enum\DepositStatus;
use App\Enum\LeaseTermType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();
            $table->foreignId('property_unit_id')->constrained('property_units')->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('renters')->cascadeOnDelete();
            $table->enum('term_type', array_column(LeaseTermType::cases(), 'value'))->default(LeaseTermType::MONTHLY->value);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            $table->decimal('security_deposit', 10, 2)->default(0);
            $table->decimal('advance_rent', 10, 2)->default(0);
            $table->timestamp('advance_rent_applied_at')->nullable();
            $table->enum('deposit_status', array_column(DepositStatus::cases(), 'value'))->default(DepositStatus::HELD->value);
            $table->json('deposit_deductions')->nullable();
            $table->decimal('deposit_refunded_amount', 10, 2)->nullable();
            $table->timestamp('deposit_refunded_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};