<?php

use App\Enum\AmenityCategory;
use App\Enum\AmenityScope;
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
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();
            $table->foreignId('tenant_business_id')->nullable()->constrained('tenant_businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('category', array_column(AmenityCategory::cases(), 'value'))->nullable();
            $table->enum('scope', array_column(AmenityScope::cases(), 'value'))->default(AmenityScope::BOTH->value);
            $table->string('icon')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('category');
            $table->index('scope');
            $table->index('tenant_business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
