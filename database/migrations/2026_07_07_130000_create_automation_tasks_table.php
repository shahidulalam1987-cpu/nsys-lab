<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_key')->unique();
            $table->string('title');
            $table->string('priority')->default('medium');
            $table->string('status')->default('pending');
            $table->string('department')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('related_module')->nullable();
            $table->string('related_record_type')->nullable();
            $table->unsignedBigInteger('related_record_id')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['department', 'status']);
            $table->index(['related_record_type', 'related_record_id']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_tasks');
    }
};
