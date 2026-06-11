<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->decimal('spend', 14, 2)->default(0);
            $table->unsignedInteger('messages')->default(0);
            $table->unsignedInteger('results')->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('cpm', 14, 2)->default(0);
            $table->decimal('cpr', 14, 2)->default(0);
            $table->decimal('cpl', 14, 2)->default(0);
            $table->decimal('cpp', 14, 2)->default(0);
            $table->decimal('cpc', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'report_date']);
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_performance_reports');
    }
};
