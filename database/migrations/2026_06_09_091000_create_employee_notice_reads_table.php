<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_notice_reads')) {
            return;
        }

        Schema::create('employee_notice_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['employee_notice_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_notice_reads');
    }
};
