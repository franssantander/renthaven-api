<?php

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
        Schema::create('permission_per_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_module_id')->constrained('permission_modules')->cascadeOnDelete();
            $table->foreignId('permission_action_id')->constrained('permission_actions')->cascadeOnDelete();

            // Ensures a user cannot have the exact same permission row duplicated
            $table->unique(['user_id', 'permission_module_id', 'permission_action_id'], 'user_module_action_unique');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_per_user');
    }
};