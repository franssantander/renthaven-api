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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'))->unique();

            // The recipient. Every notification row belongs to exactly one user,
            // which is what enforces visibility scoping on read.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_business_id')->nullable()->constrained('tenant_businesses')->cascadeOnDelete();
            $table->foreignId('audit_log_id')->nullable()->constrained('audit_logs')->nullOnDelete();

            $table->string('module')->index();
            $table->string('action')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at', 'id'], 'notifications_user_created_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
