<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePhase2AAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_create_saves_phase_2a_fields_and_defaults_salary_day(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/admin/employees', [
            'name' => 'Phase Two Employee',
            'mobile' => '01711111111',
            'email' => 'phase.employee@example.com',
            'address' => 'Dhaka Office',
            'nid_number' => '1234567890',
            'date_of_birth' => '1995-04-10',
            'gender' => 'male',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-09',
            'last_working_date' => null,
            'status' => 'probation',
            'salary_day' => null,
            'monthly_salary' => 20000,
            'bank_name' => 'DBBL',
            'account_name' => 'Phase Two Employee',
            'account_number' => '123456',
            'branch_name' => 'Banani',
            'bkash_number' => '01722222222',
            'nagad_number' => '01733333333',
            'rocket_number' => '01744444444',
            'preferred_payment_method' => 'bkash',
            'mobile_banking_info' => 'Use bKash first',
            'admin_note' => 'Trusted test employee',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', [
            'name' => 'Phase Two Employee',
            'email' => 'phase.employee@example.com',
            'address' => 'Dhaka Office',
            'nid_number' => '1234567890',
            'gender' => 'male',
            'salary_day' => 9,
            'branch_name' => 'Banani',
            'bkash_number' => '01722222222',
            'preferred_payment_method' => 'bkash',
            'admin_note' => 'Trusted test employee',
        ]);
    }

    public function test_employee_list_shows_status_summary_and_final_labels(): void
    {
        $admin = $this->admin();
        $this->employee(['name' => 'Active Person', 'status' => 'active']);
        $this->employee(['name' => 'Inactive Person', 'status' => 'inactive']);

        $response = $this->actingAs($admin)->get('/admin/employees');

        $response->assertOk();
        $response->assertSee('Total Employees');
        $response->assertSee('All Employees');
        $response->assertSee('Active');
        $response->assertSee('Inactive');
        $response->assertSee('Terminated');
    }

    public function test_employee_profile_shows_phase_2a_sections_and_salary_summary(): void
    {
        $admin = $this->admin();
        $employee = $this->employee([
            'email' => 'profile.employee@example.com',
            'address' => 'Mirpur',
            'nid_number' => '998877',
            'salary_day' => 10,
            'bkash_number' => '01755555555',
            'admin_note' => 'Profile note',
        ]);
        $employee->salaryDays()->create([
            'client_id' => $this->client()->id,
            'date' => '2026-06-01',
            'is_counted' => true,
            'reason' => 'active_working',
        ]);
        $employee->payrolls()->create([
            'salary_month' => '2026-06-01',
            'payable_salary' => 1000,
            'paid_amount' => 400,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);

        $response->assertOk();
        $response->assertSee('Basic Information');
        $response->assertSee('Employment Information');
        $response->assertSee('Login Information');
        $response->assertSee('Banking / Mobile Banking Information');
        $response->assertSee('Salary Overview');
        $response->assertSee('Documents');
        $response->assertSee('Admin Notes');
        $response->assertSee('profile.employee@example.com');
        $response->assertSee('Current Salary Due');
        $response->assertSee('BDT 600.00');
        $response->assertSee('Profile note');
    }

    public function test_admin_client_details_shows_employee_department_summary(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'employee_id' => 'NSYS-EM-222',
            'name' => 'Client Assigned Employee',
            'status' => 'active',
            'monthly_salary' => 25000,
        ]);
        $employee->assignments()->create([
            'client_id' => $client->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/clients/' . $client->id);

        $response->assertOk();
        $response->assertSee('Employee Department Summary');
        $response->assertSee('Total Assigned');
        $response->assertSee('NSYS-EM-222');
        $response->assertSee('Client Assigned Employee');
        $response->assertSee('Active-Working');
        $response->assertSee('BDT 25,000.00');
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
            'name' => 'Phase 2A Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'probation',
            'monthly_salary' => 10000,
        ], $overrides));
    }

    private function client(): Client
    {
        $clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        return Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Phase 2A Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
    }
}
