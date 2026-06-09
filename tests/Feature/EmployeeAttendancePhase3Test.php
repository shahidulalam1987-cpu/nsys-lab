<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeWorkStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAttendancePhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_can_check_in_once_and_view_own_attendance(): void
    {
        Carbon::setTestNow('2026-06-08 09:30:00');

        $employeeUser = $this->user('employee');
        $client = $this->client();
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
        ]);
        $employee->assignments()->create([
            'client_id' => $client->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($employeeUser)->post('/employee/attendance/check-in');

        $response->assertRedirect();
        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'attendance_date' => '2026-06-08 00:00:00',
            'status' => 'present',
            'is_working_day' => 1,
        ]);

        $this->actingAs($employeeUser)
            ->post('/employee/attendance/check-in')
            ->assertSessionHasErrors('attendance');

        $this->actingAs($employeeUser)
            ->get('/employee/attendance')
            ->assertOk()
            ->assertSee('Present')
            ->assertSee('Attendance is for shift monitoring only');
    }

    public function test_admin_can_filter_update_delete_and_export_attendance(): void
    {
        $admin = $this->user('admin');
        $client = $this->client(['company_name' => 'Attendance Client']);
        $employee = $this->employee(['name' => 'Attendance Employee']);
        $attendance = $employee->attendances()->create([
            'client_id' => $client->id,
            'attendance_date' => '2026-06-08',
            'check_in_at' => '2026-06-08 09:30:00',
            'status' => 'present',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance?employee_id=' . $employee->id)
            ->assertOk()
            ->assertSee('Attendance Employee')
            ->assertSee('Total Present');

        $this->actingAs($admin)
            ->post('/admin/attendance/' . $attendance->id . '/update', [
                'client_id' => $client->id,
                'status' => 'boosting_off',
                'is_working_day' => 0,
                'check_in_at' => '2026-06-08 09:30:00',
                'note' => 'Boosting paused',
            ])
            ->assertRedirect('/admin/attendance');

        $attendance->refresh();
        $this->assertSame('boosting_off', $attendance->status);
        $this->assertFalse($attendance->is_working_day);

        $csv = $this->actingAs($admin)->get('/admin/attendance/export?status=boosting_off');
        $csv->assertOk();
        $csv->assertDownload('employee-attendance-report.csv');
        $this->assertStringContainsString('Boosting OFF', $csv->streamedContent());

        $this->actingAs($admin)
            ->post('/admin/attendance/' . $attendance->id . '/delete')
            ->assertRedirect('/admin/attendance');
        $this->assertDatabaseMissing('employee_attendances', ['id' => $attendance->id]);
    }

    public function test_admin_can_manage_and_export_work_status_records(): void
    {
        $admin = $this->user('admin');
        $client = $this->client(['company_name' => 'Work Status Client']);
        $employee = $this->employee(['name' => 'Work Status Employee']);

        $this->actingAs($admin)
            ->post('/admin/work-status', [
                'employee_id' => $employee->id,
                'client_id' => $client->id,
                'work_date' => '2026-06-08',
                'status' => 'half_day',
                'note' => 'Half shift',
            ])
            ->assertRedirect();

        $workStatus = $employee->workStatuses()->first();
        $this->assertSame('half_day', $workStatus->status);
        $this->assertSame(0.5, (float) $workStatus->salary_count_value);

        $this->actingAs($admin)
            ->get('/admin/work-status?employee_id=' . $employee->id)
            ->assertOk()
            ->assertSee('Work Status Employee')
            ->assertSee('Half Day')
            ->assertSee('0.50');

        $csv = $this->actingAs($admin)->get('/admin/work-status/export?status=half_day');
        $csv->assertOk();
        $csv->assertDownload('employee-work-status-report.csv');
        $this->assertStringContainsString('Half Day', $csv->streamedContent());
    }

    public function test_salary_generate_can_use_work_status_records_for_working_days(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Nahid',
            'monthly_salary' => 30000,
        ]);

        foreach (range(1, 8) as $day) {
            $this->workStatus($employee, $client, '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT), 'working');
        }
        $this->workStatus($employee, $client, '2026-06-09', 'half_day');
        $this->workStatus($employee, $client, '2026-06-10', 'half_day');

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'use_work_status_records' => 1,
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame(9.0, (float) $payroll->working_days);
        $this->assertSame(0.0, (float) $payroll->non_working_days);
        $this->assertSame(9000.0, (float) $payroll->payable_salary);
        $this->assertCount(10, $payroll->salary_day_adjustments);
        $this->assertSame(0.5, $payroll->salary_day_adjustments[8]['salary_count_value']);
        $this->assertSame(0.5, $payroll->salary_day_adjustments[9]['salary_count_value']);
    }

    public function test_attendance_records_do_not_drive_salary_generation(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);

        foreach (range(1, 10) as $day) {
            $this->attendance($employee, $client, '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT), 'present');
        }

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'use_work_status_records' => 1,
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame(0.0, (float) $payroll->working_days);
        $this->assertSame(10.0, (float) $payroll->non_working_days);
        $this->assertSame(0.0, (float) $payroll->payable_salary);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function client(array $overrides = []): Client
    {
        $clientUser = $this->user('client');

        return Client::create(array_merge([
            'user_id' => $clientUser->id,
            'company_name' => 'Attendance Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ], $overrides));
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Attendance Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 30000,
        ], $overrides));
    }

    private function attendance(Employee $employee, Client $client, string $date, string $status): EmployeeAttendance
    {
        return $employee->attendances()->create([
            'client_id' => $client->id,
            'attendance_date' => $date,
            'check_in_at' => $status === 'present' ? $date . ' 09:30:00' : null,
            'status' => $status,
        ]);
    }

    private function workStatus(Employee $employee, Client $client, string $date, string $status): EmployeeWorkStatus
    {
        return $employee->workStatuses()->create([
            'client_id' => $client->id,
            'work_date' => $date,
            'status' => $status,
        ]);
    }
}
