<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeWorkStatus;
use App\Models\Shift;
use App\Models\User;
use App\Services\WorkStatusCycleService;
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

    public function test_admin_can_create_bulk_date_range_work_status_and_update_duplicates(): void
    {
        $admin = $this->user('admin');
        $client = $this->client(['company_name' => 'Bulk Work Client']);
        $employee = $this->employee(['name' => 'Bulk Work Employee']);

        $employee->workStatuses()->create([
            'client_id' => $client->id,
            'work_date' => '2026-06-10',
            'status' => 'half_day',
            'salary_count_value' => 0.5,
        ]);

        $this->actingAs($admin)
            ->get('/admin/work-status/create')
            ->assertOk()
            ->assertSee('Single Date')
            ->assertSee('Date Range')
            ->assertSee('Monthly Cycle')
            ->assertSee('Salary Cycle Date')
            ->assertSee('Use Date Range when adding multiple work status records at once.');

        $response = $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'range',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'from_date' => '2026-06-09',
            'to_date' => '2026-06-11',
            'status' => 'working',
            'note' => 'Bulk entry',
        ]);

        $response->assertRedirect('/admin/work-status');
        $response->assertSessionHas('success', 'Bulk work status saved. Created: 2, Updated: 1, Skipped: 0.');

        $this->assertSame(3, $employee->workStatuses()->count());
        $this->assertDatabaseHas('employee_work_statuses', [
            'employee_id' => $employee->id,
            'work_date' => '2026-06-09 00:00:00',
            'status' => 'working',
            'salary_count_value' => 1,
        ]);
        $this->assertDatabaseHas('employee_work_statuses', [
            'employee_id' => $employee->id,
            'work_date' => '2026-06-10 00:00:00',
            'status' => 'working',
            'salary_count_value' => 1,
            'note' => 'Bulk entry',
        ]);
        $this->assertDatabaseHas('employee_work_statuses', [
            'employee_id' => $employee->id,
            'work_date' => '2026-06-11 00:00:00',
            'status' => 'working',
            'salary_count_value' => 1,
        ]);
    }

    public function test_bulk_work_status_validates_range_limit_and_last_working_date_override(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'status' => 'terminated',
            'last_working_date' => '2026-06-10',
        ]);

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'range',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'from_date' => '2026-06-01',
            'to_date' => '2026-08-01',
            'status' => 'working',
        ])->assertSessionHasErrors('to_date');

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'range',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'from_date' => '2026-06-09',
            'to_date' => '2026-06-11',
            'status' => 'working',
        ])->assertSessionHasErrors('to_date');

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'range',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'from_date' => '2026-06-09',
            'to_date' => '2026-06-11',
            'status' => 'working',
            'confirm_after_last_working_date' => 1,
        ])->assertSessionHas('success', 'Bulk work status saved. Created: 3, Updated: 0, Skipped: 0.');

        $this->assertSame(3, $employee->workStatuses()->count());
    }

    public function test_work_status_create_exposes_latest_active_assignment_defaults(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee(['confirmation_date' => '2026-05-01', 'salary_day' => 12]);
        $shift = Shift::create(['name' => 'Morning Shift', 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'active']);
        $page = $client->pages()->create(['page_name' => 'Latest Client Page', 'platform' => 'Facebook', 'status' => 'active']);
        $employee->assignments()->create([
            'client_id' => $client->id,
            'assigned_from' => '2026-05-01',
            'status' => 'active',
        ]);
        $employee->assignments()->create([
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/work-status/create?employee_id=' . $employee->id . '&entry_mode=monthly&salary_month=2026-06');

        $response->assertOk();
        $response->assertSee('"has_assignment":true', false);
        $response->assertSee('"client_id":' . $client->id, false);
        $response->assertSee('"client_page_id":' . $page->id, false);
        $response->assertSee('"shift_id":' . $shift->id, false);
        $response->assertSee('Latest active assignment loaded.');
    }

    public function test_monthly_cycle_keeps_agency_internal_client_and_page_empty(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $page = $client->pages()->create(['page_name' => 'Ignored Page', 'platform' => 'Facebook', 'status' => 'active']);
        $shift = Shift::create(['name' => 'Night Shift', 'start_time' => '17:00', 'end_time' => '01:00', 'status' => 'active']);
        $employee = $this->employee([
            'employee_type' => 'agency_internal',
            'confirmation_date' => '2026-06-10',
            'salary_day' => 12,
            'shift_id' => $shift->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'salary_month' => '2026-06',
            'duplicate_action' => 'skip',
            'status' => 'working',
            'monthly_rows' => $this->monthlyRows('2026-06-10', '2026-06-12'),
        ]);

        $response->assertRedirect('/admin/payroll?status=due');
        $this->assertSame(3, $employee->workStatuses()->count());
        $this->assertSame(0, $employee->workStatuses()->whereNotNull('client_id')->count());
        $this->assertSame(0, $employee->workStatuses()->whereNotNull('client_page_id')->count());
        $this->assertSame(3, $employee->workStatuses()->where('shift_id', $shift->id)->count());
    }

    public function test_monthly_cycle_starts_at_confirmation_date(): void
    {
        $admin = $this->user('admin');
        $employee = $this->employee(['confirmation_date' => '2026-05-16', 'salary_day' => 16]);

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-06',
            'duplicate_action' => 'skip',
            'status' => 'working',
            'monthly_rows' => $this->monthlyRows('2026-05-16', '2026-06-16'),
        ])->assertRedirect('/admin/payroll?status=due');

        $this->assertDatabaseHas('employee_work_statuses', ['employee_id' => $employee->id, 'work_date' => '2026-05-16 00:00:00']);
        $this->assertSame('2026-05-16', $employee->workStatuses()->oldest('work_date')->first()->work_date->toDateString());
        $this->assertSame(32, $employee->workStatuses()->count());
    }

    public function test_monthly_cycle_does_not_end_on_confirmation_day_when_salary_day_matches(): void
    {
        $employee = $this->employee([
            'confirmation_date' => '2026-06-12',
            'salary_day' => 12,
        ]);

        $period = app(WorkStatusCycleService::class)->period($employee, '2026-06');

        $this->assertSame('2026-06-12', $period['period_start']->toDateString());
        $this->assertSame('2026-07-12', $period['period_end']->toDateString());
        $this->assertSame('2026-07', $period['salary_month']->format('Y-m'));
    }

    public function test_next_monthly_cycle_starts_after_previous_salary_cycle_date(): void
    {
        $admin = $this->user('admin');
        $employee = $this->employee(['confirmation_date' => '2026-05-16', 'salary_day' => 16]);

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-07',
            'status' => 'working',
            'monthly_rows' => $this->monthlyRows('2026-06-17', '2026-07-16'),
        ])->assertRedirect('/admin/payroll?status=due');

        $this->assertSame('2026-06-17', $employee->workStatuses()->oldest('work_date')->first()->work_date->toDateString());
        $this->assertSame(30, $employee->workStatuses()->count());
    }

    public function test_monthly_cycle_ends_on_current_salary_day(): void
    {
        $admin = $this->user('admin');
        $employee = $this->employee(['confirmation_date' => '2026-05-16', 'salary_day' => 16]);

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-07',
            'status' => 'working',
            'monthly_rows' => $this->monthlyRows('2026-06-17', '2026-07-17'),
        ])->assertRedirect('/admin/payroll?status=due');

        $this->assertDatabaseHas('employee_work_statuses', ['employee_id' => $employee->id, 'work_date' => '2026-07-16 00:00:00']);
        $this->assertDatabaseMissing('employee_work_statuses', ['employee_id' => $employee->id, 'work_date' => '2026-07-17']);
    }

    public function test_monthly_cycle_stops_at_terminated_employee_last_working_date(): void
    {
        $admin = $this->user('admin');
        $employee = $this->employee([
            'confirmation_date' => '2026-06-01',
            'salary_day' => 12,
            'status' => 'terminated',
            'last_working_date' => '2026-06-10',
        ]);

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-06',
            'duplicate_action' => 'skip',
            'status' => 'working',
            'monthly_rows' => $this->monthlyRows('2026-06-01', '2026-06-12'),
        ])->assertRedirect('/admin/payroll?status=due');

        $this->assertSame(10, $employee->workStatuses()->count());
        $this->assertDatabaseMissing('employee_work_statuses', ['employee_id' => $employee->id, 'work_date' => '2026-06-11']);
    }

    public function test_monthly_cycle_skips_duplicate_dates_by_default(): void
    {
        $admin = $this->user('admin');
        $employee = $this->employee(['confirmation_date' => '2026-06-10', 'salary_day' => 12]);
        $employee->workStatuses()->create([
            'work_date' => '2026-06-11',
            'status' => 'half_day',
            'salary_count_value' => 0.5,
            'note' => 'Keep existing',
        ]);

        $response = $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-06',
            'status' => 'working',
            'monthly_rows' => $this->monthlyRows('2026-06-10', '2026-06-12'),
        ]);

        $response->assertSessionHas('success', 'Monthly cycle work status saved. Created: 2, Updated: 0, Skipped: 1.');
        $this->assertDatabaseHas('employee_work_statuses', [
            'employee_id' => $employee->id,
            'work_date' => '2026-06-11 00:00:00',
            'status' => 'half_day',
            'note' => 'Keep existing',
        ]);
    }

    public function test_employee_becomes_salary_ready_after_monthly_work_status_creation(): void
    {
        Carbon::setTestNow('2026-06-15');
        $admin = $this->user('admin');
        $employee = $this->employee([
            'name' => 'Monthly Cycle Ready Employee',
            'employee_type' => 'agency_internal',
            'confirmation_date' => '2026-06-10',
            'salary_day' => 12,
        ]);

        $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-06',
            'status' => 'working',
            'monthly_rows' => $this->monthlyRows('2026-06-10', '2026-06-12'),
            'return_to' => '/admin/payroll?status=due',
        ])->assertRedirect('/admin/payroll?status=due');

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');
        $response->assertOk();
        $response->assertSee('Monthly Cycle Ready Employee');
        $response->assertSee('Salary Ready');
        $response->assertSee('Generate Salary');
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

        $response->assertSessionHasErrors(['work_status' => 'Work Status records are required before salary generation.']);
        $this->assertSame(0, $employee->payrolls()->count());
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

    private function monthlyRows(string $from, string $to, string $status = 'working'): array
    {
        $rows = [];

        for ($date = Carbon::parse($from); $date->lte(Carbon::parse($to)); $date->addDay()) {
            $rows[] = [
                'date' => $date->toDateString(),
                'day_type' => $status === 'half_day' ? 'half_day' : ($status === 'working' ? 'working' : 'non_working'),
                'status' => $status,
            ];
        }

        return $rows;
    }
}
