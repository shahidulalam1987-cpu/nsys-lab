<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFormOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_create_auto_generates_next_nsys_employee_id(): void
    {
        $admin = $this->admin();
        $this->employee([
            'employee_id' => 'LEGACY-100',
            'department' => 'Operations',
            'role' => 'Operator',
        ]);
        $this->employee([
            'employee_id' => 'NSYS-EM-001',
            'department' => 'Operations',
            'role' => 'Operator',
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees', $this->validPayload([
            'name' => 'Auto Code Employee',
        ]));

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', [
            'employee_id' => 'NSYS-EM-002',
            'name' => 'Auto Code Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
        ]);
    }

    public function test_employee_create_reuses_first_available_nsys_employee_id_gap(): void
    {
        $admin = $this->admin();
        $this->employee(['employee_id' => 'NSYS-EM-001']);
        $this->employee(['employee_id' => 'NSYS-EM-003']);

        $response = $this->actingAs($admin)->post('/admin/employees', $this->validPayload([
            'name' => 'Gap Code Employee',
        ]));

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', [
            'employee_id' => 'NSYS-EM-002',
            'name' => 'Gap Code Employee',
        ]);
    }

    public function test_employee_create_validates_department_and_role_options(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/admin/employees', $this->validPayload([
            'department' => 'Operations',
            'role' => 'Operator',
        ]));

        $response->assertSessionHasErrors(['department', 'role']);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_employee_update_does_not_change_employee_id(): void
    {
        $admin = $this->admin();
        $employee = $this->employee([
            'employee_id' => 'OLD-EMP-77',
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/update', $this->validPayload([
            'employee_id' => 'NSYS-EM-999',
            'name' => 'Updated Name',
            'department' => 'Support',
            'role' => 'Team Leader',
        ]));

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $employee->refresh();

        $this->assertSame('OLD-EMP-77', $employee->employee_id);
        $this->assertSame('Updated Name', $employee->name);
        $this->assertSame('Support', $employee->department);
        $this->assertSame('Team Leader', $employee->role);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Existing Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => now()->toDateString(),
            'status' => 'probation',
            'monthly_salary' => 10000,
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Employee',
            'mobile' => '01700000000',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => now()->toDateString(),
            'confirmation_date' => null,
            'last_working_date' => null,
            'status' => 'probation',
            'monthly_salary' => 15000,
            'bank_name' => null,
            'account_name' => null,
            'account_number' => null,
            'mobile_banking_info' => null,
        ], $overrides);
    }
}
