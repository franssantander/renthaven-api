<?php

use App\Enum\MaintenanceCategory;
use App\Enum\MaintenancePriority;
use App\Enum\MaintenanceRequestStatus;
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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();

            $table->foreignId('property_unit_id')->constrained('property_units')->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained('leases')->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('renters')->cascadeOnDelete();
            $table->foreignId('tenant_business_id')->constrained('tenant_businesses')->cascadeOnDelete();

            $table->string('title');
            $table->text('description');
            $table->enum('category', array_column(MaintenanceCategory::cases(), 'value'));
            $table->enum('priority', array_column(MaintenancePriority::cases(), 'value'))->default(MaintenancePriority::MEDIUM->value);
            $table->enum('status', array_column(MaintenanceRequestStatus::cases(), 'value'))->default(MaintenanceRequestStatus::OPEN->value);

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_business_id', 'status']);
            $table->index(['property_unit_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
