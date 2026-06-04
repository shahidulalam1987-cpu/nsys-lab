<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('company_name');
            $table->string('phone')->nullable();

            $table->decimal('client_rate', 10, 2)->default(0);
            $table->decimal('buy_rate', 10, 2)->default(0);

            $table->enum('status', [
                'active',
                'pending',
                'inactive'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};