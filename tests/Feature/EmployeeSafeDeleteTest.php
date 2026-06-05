<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSafeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_terminate_employee_without_deleting_login_or_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
            'status' => 'active',
            'last_working_date' => null,
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/terminate');

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $employee->refresh();

        $this->assertSame('terminated', $employee->status);
        $this->assertSame(now()->toDateString(), $employee->last_working_date->toDateString());
        $this->assertDatabaseHas('users', [
            'id' => $employeeUser->id,
            'role' => 'employee',
        ]);
    }

    public function test_delete_is_blocked_when_employee_has_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        $client = Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'History Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
        $employee = $this->employee();
        $employee->assignments()->create([
            'client_id' => $client->id,
            'assigned_from' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/delete');

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $response->assertSessionHas('success', 'This employee has history. Please terminate instead.');
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
    }

    public function test_delete_removes_history_free_employee_and_linked_employee_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/delete');

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
        ]);
        $this->assertDatabaseMissing('users', [
            'id' => $employeeUser->id,
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Safe Delete Employee',
            'department' => 'Operations',
            'role' => 'Operator',
            'joining_date' => now()->toDateString(),
            'status' => 'probation',
            'monthly_salary' => 10000,
        ], $overrides));
    }
}
