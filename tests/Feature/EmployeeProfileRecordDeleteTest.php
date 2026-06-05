<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileRecordDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_one_employee_assignment_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $client = $this->client();
        $employee = $this->employee();
        $assignment = $employee->assignments()->create([
            'client_id' => $client->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
            'note' => 'Wrong test assignment',
        ]);

        $response = $this->actingAs($admin)
            ->post('/admin/employee-assignments/' . $assignment->id . '/delete');

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $response->assertSessionHas('success', 'Assignment deleted successfully.');
        $this->assertDatabaseMissing('employee_assignments', [
            'id' => $assignment->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_admin_can_delete_one_salary_day_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $client = $this->client();
        $employee = $this->employee();
        $salaryDay = $employee->salaryDays()->create([
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'is_counted' => true,
            'reason' => 'active_working',
            'note' => 'Wrong test day',
        ]);

        $response = $this->actingAs($admin)
            ->post('/admin/salary-days/' . $salaryDay->id . '/delete');

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $response->assertSessionHas('success', 'Salary day deleted successfully.');
        $this->assertDatabaseMissing('salary_days', [
            'id' => $salaryDay->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Profile Record Employee',
            'department' => 'Operations',
            'role' => 'Operator',
            'joining_date' => now()->toDateString(),
            'status' => 'probation',
            'monthly_salary' => 10000,
        ], $overrides));
    }

    private function client(array $overrides = []): Client
    {
        $clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        return Client::create(array_merge([
            'user_id' => $clientUser->id,
            'company_name' => 'Record Delete Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ], $overrides));
    }
}
