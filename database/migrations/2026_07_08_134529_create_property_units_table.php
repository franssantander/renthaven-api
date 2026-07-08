<?php

use App\Enum\PropertyUnitStatus;
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
        Schema::create('property_units', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('name');
            $table->integer('capacity')->default(1);
            $table->decimal('rent_price', 10, 2);
            $table->enum('status', array_column(PropertyUnitStatus::cases(), 'value'))->default(PropertyUnitStatus::AVAILABLE->value);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'deleted_at']);
            $table->index(['property_id', 'capacity']);
            $table->index(['property_id', 'rent_price']);
            $table->index(['property_id', 'status']);
            $table->index(['property_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_units');
    }
};