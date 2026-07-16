<?php

use App\Enum\LeaseHistoryAction;
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
        Schema::create('lease_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();

            $table->foreignId('lease_id')->constrained('leases')->cascadeOnDelete();
            $table->foreignId('previous_lease_id')->nullable()->constrained('leases')->nullOnDelete();
            $table->foreignId('renter_id')->constrained('renters')->cascadeOnDelete();
            $table->foreignId('from_property_unit_id')->nullable()->constrained('property_units')->nullOnDelete();
            $table->foreignId('to_property_unit_id')->constrained('property_units')->cascadeOnDelete();

            $table->enum('action', array_column(LeaseHistoryAction::cases(), 'value'));
            $table->date('effective_date');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['renter_id', 'effective_date']);
            $table->index(['to_property_unit_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_histories');
    }
};
