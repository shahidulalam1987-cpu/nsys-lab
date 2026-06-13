<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_key')->unique();
            $table->string('type')->default('alert');
            $table->string('department');
            $table->string('priority');
            $table->string('message');
            $table->string('status')->default('unread');
            $table->string('action_url')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('target_team')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['priority', 'status']);
            $table->index(['department', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
