<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeLoginAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_link_employee_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'employee_id' => 'EMP-001',
            'name' => 'Test Employee',
            'department' => 'Operations',
            'role' => 'Operator',
            'joining_date' => now()->toDateString(),
            'status' => 'probation',
            'monthly_salary' => 10000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/create-login', [
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'employee@example.com')->first();

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $this->assertNotNull($user);
        $this->assertSame('employee', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame($user->id, $employee->fresh()->user_id);
    }

    public function test_employee_login_creation_is_not_allowed_when_already_linked(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'email' => 'linked@example.com',
        ]);

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_id' => 'EMP-002',
            'name' => 'Linked Employee',
            'department' => 'Operations',
            'role' => 'Operator',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/create-login', [
            'name' => 'Linked Employee',
            'email' => 'new-linked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $this->assertDatabaseMissing('users', [
            'email' => 'new-linked@example.com',
        ]);
        $this->assertSame($employeeUser->id, $employee->fresh()->user_id);
    }

    public function test_employee_dashboard_is_employee_only(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'user_id' => $employeeUser->id,
            'employee_id' => 'EMP-003',
            'name' => 'Dashboard Employee',
            'department' => 'Operations',
            'role' => 'Operator',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);

        $this->actingAs($employeeUser)->get('/employee/dashboard')->assertOk();
        $this->actingAs($admin)->get('/employee/dashboard')->assertForbidden();
        $this->actingAs($client)->get('/employee/dashboard')->assertForbidden();
    }
}
