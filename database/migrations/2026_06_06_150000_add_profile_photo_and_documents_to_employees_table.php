<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('gender');
            $table->string('nid_front_file')->nullable()->after('profile_photo');
            $table->string('nid_back_file')->nullable()->after('nid_front_file');
            $table->string('cv_file')->nullable()->after('nid_back_file');
            $table->string('appointment_letter_file')->nullable()->after('cv_file');
            $table->string('agreement_file')->nullable()->after('appointment_letter_file');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo',
                'nid_front_file',
                'nid_back_file',
                'cv_file',
                'appointment_letter_file',
                'agreement_file',
            ]);
        });
    }
};
