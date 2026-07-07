<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_task_id')->nullable()->constrained('automation_tasks')->nullOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rule_key');
            $table->string('event_name')->nullable();
            $table->string('result');
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['rule_key', 'created_at']);
            $table->index(['event_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_audits');
    }
};
