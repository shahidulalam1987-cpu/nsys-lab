<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@nsysagency.com'],
            [
                'name' => 'NSYS Admin',
                'password' => Hash::make('admin123456'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        if (Schema::hasTable('roles') && Schema::hasTable('user_roles')) {
            $roleId = DB::table('roles')->where('slug', 'super_admin')->value('id');

            if ($roleId) {
                DB::table('user_roles')->updateOrInsert([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                ]);
            }
        }
    }
}
