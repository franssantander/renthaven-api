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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_business_id')->nullable()->constrained('tenant_businesses')->nullOnDelete();
            $table->string('actor_email')->nullable();
            $table->string('module')->index();
            $table->string('action')->index();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->nullableMorphs('auditable');

            $table->json('context')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'action', 'created_at'], 'audit_logs_module_action_created_index');
            $table->index(['created_at', 'id'], 'audit_logs_created_id_index');
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_index');

            $table->index(['tenant_business_id', 'module', 'created_at', 'id'], 'audit_logs_tenant_module_created_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};