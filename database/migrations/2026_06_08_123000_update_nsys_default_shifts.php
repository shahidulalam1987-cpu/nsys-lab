<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shifts')) {
            return;
        }

        $now = now();
        $this->upsertShift('Morning Shift', '09:00:00', '17:00:00', $now);
        $this->renameOrUpsertNightShift($now);
        $this->upsertShift('Full Day Shift', '09:00:00', '01:00:00', $now);
    }

    public function down(): void
    {
        if (! Schema::hasTable('shifts')) {
            return;
        }

        $now = now();
        $this->upsertShift('Morning Shift', '09:00:00', '13:00:00', $now);
        $this->upsertShift('Full Day Shift', '09:00:00', '17:00:00', $now);

        $nightShift = DB::table('shifts')->where('name', 'Night Shift')->first();
        $eveningShift = DB::table('shifts')->where('name', 'Evening Shift')->first();

        if ($nightShift && ! $eveningShift) {
            DB::table('shifts')->where('id', $nightShift->id)->update([
                'name' => 'Evening Shift',
                'start_time' => '17:00:00',
                'end_time' => '01:00:00',
                'status' => 'active',
                'updated_at' => $now,
            ]);
        } else {
            $this->upsertShift('Evening Shift', '17:00:00', '01:00:00', $now);
        }
    }

    private function renameOrUpsertNightShift($now): void
    {
        $nightShift = DB::table('shifts')->where('name', 'Night Shift')->first();
        $eveningShift = DB::table('shifts')->where('name', 'Evening Shift')->first();

        if ($eveningShift && ! $nightShift) {
            DB::table('shifts')->where('id', $eveningShift->id)->update([
                'name' => 'Night Shift',
                'start_time' => '17:00:00',
                'end_time' => '01:00:00',
                'status' => 'active',
                'updated_at' => $now,
            ]);

            return;
        }

        $this->upsertShift('Night Shift', '17:00:00', '01:00:00', $now);

        if ($eveningShift) {
            DB::table('shifts')->where('id', $eveningShift->id)->update([
                'status' => 'inactive',
                'updated_at' => $now,
            ]);
        }
    }

    private function upsertShift(string $name, string $startTime, string $endTime, $now): void
    {
        DB::table('shifts')->updateOrInsert(
            ['name' => $name],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
};
