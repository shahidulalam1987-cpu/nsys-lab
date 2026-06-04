<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'client@nsysagency.com'],
            [
                'name' => 'Nahid Client',
                'password' => Hash::make('client123456'),
                'role' => 'client',
            ]
        );

        Client::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => 'Nahid',
                'phone' => '01817791836',
                'client_rate' => 145,
                'buy_rate' => 130,
                'status' => 'active',
            ]
        );
    }
}