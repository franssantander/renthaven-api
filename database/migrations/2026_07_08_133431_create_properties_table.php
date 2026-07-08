<?php

use App\Enum\PropertyType;
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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();
            $table->foreignId('tenant_business_id')->constrained('tenant_businesses')->cascadeOnDelete();
            $table->string('name');
            $table->text('address');
            $table->enum('type', array_column(PropertyType::cases(), 'value'));
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_business_id', 'deleted_at']);
            $table->index(['tenant_business_id', 'type']);
            $table->index(['tenant_business_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};