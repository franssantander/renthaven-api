<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_module_id')->constrained('permission_modules')->cascadeOnDelete();
            $table->foreignId('permission_action_id')->constrained('permission_actions')->cascadeOnDelete();
            $table->softDeletes();
            $table->unique(['role_id', 'permission_module_id', 'permission_action_id'], 'role_module_action_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};