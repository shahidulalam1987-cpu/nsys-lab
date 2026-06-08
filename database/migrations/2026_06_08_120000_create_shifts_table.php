<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        foreach ([
            ['name' => 'Morning Shift', 'start_time' => '09:00:00', 'end_time' => '17:00:00'],
            ['name' => 'Night Shift', 'start_time' => '17:00:00', 'end_time' => '01:00:00'],
            ['name' => 'Full Day Shift', 'start_time' => '09:00:00', 'end_time' => '01:00:00'],
        ] as $shift) {
            DB::table('shifts')->updateOrInsert(
                ['name' => $shift['name']],
                array_merge($shift, [
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
