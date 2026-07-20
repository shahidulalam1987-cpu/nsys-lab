<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_is_idempotent(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@nsysagency.com')->count());
    }

    public function test_seeded_admin_password_matches_expected_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin@nsysagency.com')->firstOrFail();

        $this->assertTrue(Hash::check('admin123456', $admin->password));
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_seeded_admin_can_access_admin_dashboard(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin@nsysagency.com')->firstOrFail();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_existing_users_remain_untouched(): void
    {
        $existing = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'role' => 'client',
            'status' => 'active',
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'role' => 'client',
            'status' => 'active',
        ]);
        $this->assertSame(2, User::count());
    }
}
