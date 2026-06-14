<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\EmployeeNotice;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeePortalPhase5Test extends TestCase
{
    use RefreshDatabase;

    public function test_employee_portal_shows_own_dashboard_work_salary_assignments_documents_and_notices(): void
    {
        $employeeUser = $this->user('employee');
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
            'mobile' => '01700000000',
            'appointment_letter_file' => 'employees/1/appointment.pdf',
        ]);
        $client = $this->client(['company_name' => 'Portal Client']);
        $page = ClientPage::create([
            'client_id' => $client->id,
            'page_name' => 'Portal Page',
            'page_url' => 'https://example.test/page',
            'platform' => 'Facebook',
            'status' => 'Active',
        ]);
        $shift = Shift::where('name', 'Morning Shift')->firstOrFail();

        $employee->assignments()->create([
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);
        $employee->workStatuses()->create([
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-06-03',
            'status' => 'half_day',
            'note' => 'Project handover',
        ]);
        $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'working_days' => 8.5,
            'non_working_days' => 1.5,
            'month_days' => 30,
            'daily_salary' => 1000,
            'payable_salary' => 8500,
            'paid_amount' => 5000,
            'payment_date' => '2026-06-10',
        ]);
        $notice = EmployeeNotice::create([
            'title' => 'Salary Notice',
            'category' => 'salary',
            'description' => 'Salary will be processed this week.',
            'published_at' => '2026-06-09 10:00:00',
        ]);

        $this->actingAs($employeeUser)
            ->get('/employee/dashboard')
            ->assertOk()
            ->assertSee('Portal Client')
            ->assertSee('Portal Page')
            ->assertSee('Unread Notices')
            ->assertSee('Salary Notice');

        $this->actingAs($employeeUser)
            ->get('/employee/work-status?month=2026-06&status=half_day')
            ->assertOk()
            ->assertSee('Half Day')
            ->assertSee('Project handover');

        $this->actingAs($employeeUser)
            ->get('/employee/salary')
            ->assertOk()
            ->assertSee('BDT 8,500.00')
            ->assertSee('Download Salary Statement');

        $this->actingAs($employeeUser)
            ->get('/employee/assignments')
            ->assertOk()
            ->assertSee('Portal Page')
            ->assertSee('Morning Shift');

        $this->actingAs($employeeUser)
            ->get('/employee/documents')
            ->assertOk()
            ->assertSee('Appointment Letter')
            ->assertSee('Uploaded');

        $this->actingAs($employeeUser)
            ->post('/employee/notices/' . $notice->id . '/read')
            ->assertRedirect();

        $this->assertDatabaseHas('employee_notice_reads', [
            'employee_notice_id' => $notice->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_employee_can_update_own_phone_password_and_only_download_own_salary_slip(): void
    {
        $employeeUser = $this->user('employee', ['password' => Hash::make('old-password')]);
        $otherUser = $this->user('employee');
        $employee = $this->employee(['user_id' => $employeeUser->id]);
        $otherEmployee = $this->employee([
            'user_id' => $otherUser->id,
            'employee_id' => 'EMP-OTHER',
        ]);

        $ownPayroll = $employee->payrolls()->create([
            'salary_month' => '2026-06-01',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'payable_salary' => 30000,
            'paid_amount' => 30000,
            'payment_date' => '2026-06-30',
        ]);
        $otherPayroll = $otherEmployee->payrolls()->create([
            'salary_month' => '2026-06-01',
            'working_days' => 30,
            'payable_salary' => 30000,
            'paid_amount' => 0,
        ]);

        $this->actingAs($employeeUser)
            ->post('/employee/profile', ['mobile' => '01800000000'])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'mobile' => '01800000000',
        ]);

        $this->actingAs($employeeUser)
            ->post('/employee/profile/password', [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $employeeUser->fresh()->password));

        $this->actingAs($employeeUser)
            ->get('/employee/salary/' . $ownPayroll->id . '/slip')
            ->assertOk();

        $this->actingAs($employeeUser)
            ->get('/employee/salary/' . $otherPayroll->id . '/slip')
            ->assertForbidden();
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'status' => 'active',
        ], $overrides));
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Portal Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-07',
            'status' => 'active',
            'monthly_salary' => 30000,
        ], $overrides));
    }

    private function client(array $overrides = []): Client
    {
        $clientUser = $this->user('client');

        return Client::create(array_merge([
            'user_id' => $clientUser->id,
            'company_name' => 'Portal Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ], $overrides));
    }
}
