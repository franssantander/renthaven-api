<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();

            $table->string('contact_person')->nullable();
            $table->string('tin', 15)->nullable()->unique();
            $table->text('business_address')->nullable();
            $table->string('logo_url')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_businesses');
    }
};
