<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('invoice_number')->unique();

            $table->string('title');

            $table->text('description')->nullable();

            $table->decimal('amount', 15, 2);

            $table->date('issue_date');

            $table->date('due_date');

            $table->enum('status', [
                'draft',
                'sent',
                'paid',
                'overdue',
                'cancelled'
            ])->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};