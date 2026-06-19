<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeWorkStatus;
use App\Models\User;
use App\Services\SalaryStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeePayrollDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_salary_by_date_range_without_salary_days(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'paid_amount' => 5000,
            'payment_method' => 'bKash',
            'payment_date' => '2026-06-10',
            'note' => 'Date range salary',
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $payroll->refresh();

        $this->assertSame('2026-06-01', $payroll->from_date->toDateString());
        $this->assertSame('2026-06-10', $payroll->to_date->toDateString());
        $this->assertSame('2026-06-01', $payroll->salary_period_from->toDateString());
        $this->assertSame('2026-06-10', $payroll->salary_period_to->toDateString());
        $this->assertSame('2026-06-01', $payroll->salary_month->toDateString());
        $this->assertSame('partial', $payroll->calculated_status);
        $this->assertSame(10000.0, (float) $payroll->payable_salary);
        $this->assertSame(30, $payroll->month_days);
        $this->assertSame(1000.0, (float) $payroll->daily_salary);

        $this->assertDatabaseHas('employee_payrolls', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'working_days' => 10,
            'non_working_days' => 0,
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'status' => 'partial',
        ]);
    }

    public function test_admin_can_preview_and_generate_salary_from_work_status_records(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Work Status Salary Employee',
            'monthly_salary' => 5000,
        ]);

        foreach (range(1, 14) as $day) {
            $this->workStatus($employee, $client, '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT), 'working');
        }
        $this->workStatus($employee, $client, '2026-06-15', 'half_day');
        $this->workStatus($employee, $client, '2026-06-16', 'on_leave');

        $preview = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'work_status',
            'work_status_action' => 'preview',
            'salary_month' => '2026-06',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
        ]);

        $preview->assertOk();
        $preview->assertSee('Salary Preview');
        $preview->assertSee('Work Status Salary Employee');
        $preview->assertSee('14.50');
        $preview->assertSee('BDT 2,416.72');
        $preview->assertSee('No');

        $generate = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'work_status',
            'work_status_action' => 'generate',
            'salary_month' => '2026-06',
            'rows' => [
                [
                    'employee_id' => $employee->id,
                    'client_id' => $client->id,
                    'action' => 'generate',
                ],
            ],
        ]);

        $payroll = $employee->payrolls()->first();

        $generate->assertRedirect('/admin/payroll');
        $generate->assertSessionHas('success', 'Work Status salary generation complete. Created: 1, Regenerated: 0, Skipped: 0.');
        $this->assertSame('monthly_cycle', $payroll->calculation_type);
        $this->assertSame(14.5, (float) $payroll->working_days);
        $this->assertSame(1.0, (float) $payroll->non_working_days);
        $this->assertSame(30, $payroll->month_days);
        $this->assertSame(166.67, (float) $payroll->daily_salary);
        $this->assertSame(2416.72, (float) $payroll->payable_salary);
        $this->assertCount(16, $payroll->salary_day_adjustments);
    }

    public function test_generated_salary_caps_thirty_two_work_status_days_and_pdf_shows_cap(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'confirmation_date' => '2026-05-16',
            'salary_day' => 16,
            'monthly_salary' => 5000,
        ]);

        for ($date = \Carbon\Carbon::parse('2026-05-16'); $date->lte(\Carbon\Carbon::parse('2026-06-16')); $date->addDay()) {
            $this->workStatus($employee, $client, $date->toDateString(), 'working');
        }

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-05-16',
            'to_date' => '2026-06-16',
            'use_work_status_records' => 1,
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();
        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame(32.0, (float) $payroll->working_days);
        $this->assertSame(5000.0, (float) $payroll->payable_salary);
        $this->assertLessThanOrEqual((float) $employee->monthly_salary, (float) $payroll->payable_salary);

        $pdfHtml = view('employee.pdf.salary-statement', app(SalaryStatementService::class)->data($payroll))->render();
        $this->assertStringContainsString('Work Status Count:', $pdfHtml);
        $this->assertStringContainsString('32.00', $pdfHtml);
        $this->assertStringContainsString('Payable Count:', $pdfHtml);
        $this->assertStringContainsString('30.00', $pdfHtml);
        $this->assertStringContainsString('Cap Applied:', $pdfHtml);
        $this->assertStringContainsString('Yes', $pdfHtml);
    }

    public function test_work_status_salary_preview_marks_existing_payroll_and_can_skip_or_regenerate(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);

        foreach (range(1, 10) as $day) {
            $this->workStatus($employee, $client, '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT), 'working');
        }

        $existing = $employee->payrolls()->create([
            'client_id' => $client->id,
            'calculation_type' => 'monthly_cycle',
            'salary_month' => '2026-06-01',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-30',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 10,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'payable_salary' => 10000,
            'paid_amount' => 0,
        ]);

        $preview = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'work_status',
            'work_status_action' => 'preview',
            'salary_month' => '2026-06',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
        ]);

        $preview->assertOk();
        $preview->assertSee('Yes - #' . $existing->id);
        $preview->assertSee('Regenerate');

        $skip = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'work_status',
            'work_status_action' => 'generate',
            'salary_month' => '2026-06',
            'rows' => [
                [
                    'employee_id' => $employee->id,
                    'client_id' => $client->id,
                    'action' => 'skip',
                ],
            ],
        ]);

        $skip->assertRedirect('/admin/payroll');
        $skip->assertSessionHas('success', 'Work Status salary generation complete. Created: 0, Regenerated: 0, Skipped: 1.');
        $this->assertSame(1, $employee->payrolls()->count());

        $regenerate = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'work_status',
            'work_status_action' => 'generate',
            'salary_month' => '2026-06',
            'rows' => [
                [
                    'employee_id' => $employee->id,
                    'client_id' => $client->id,
                    'action' => 'regenerate',
                ],
            ],
        ]);

        $latest = $employee->payrolls()->orderByDesc('id')->first();

        $regenerate->assertRedirect('/admin/payroll');
        $regenerate->assertSessionHas('success', 'Work Status salary generation complete. Created: 0, Regenerated: 1, Skipped: 0.');
        $this->assertSame(2, $employee->payrolls()->count());
        $this->assertSame('regenerated', $latest->generation_status);
        $this->assertSame($existing->id, $latest->regenerated_from_id);
    }

    public function test_payable_salary_uses_fixed_thirty_day_daily_rate(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 10000,
        ]);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'paid_amount' => 10000,
            'payment_method' => 'Bank Transfer',
            'payment_date' => '2026-06-30',
        ]);

        $payroll = $employee->payrolls()->first();

        $this->assertSame(10000.0, (float) $payroll->payable_salary);
        $this->assertSame('paid', $payroll->calculated_status);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-04',
            'working_days' => 4,
            'non_working_days' => 0,
            'paid_amount' => 0,
            'confirm_regenerate' => 1,
        ]);

        $payroll = $employee->payrolls()->orderByDesc('id')->first();

        $this->assertSame(1333.32, (float) $payroll->payable_salary);
    }

    public function test_salary_uses_fixed_thirty_day_policy_examples(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 5000,
        ]);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-14',
            'working_days' => 14,
            'non_working_days' => 0,
            'paid_amount' => 0,
        ]);

        $fourteenDays = $employee->payrolls()->first();

        $this->assertSame(30, $fourteenDays->month_days);
        $this->assertSame(166.67, (float) $fourteenDays->daily_salary);
        $this->assertSame(2333.38, (float) $fourteenDays->payable_salary);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-15',
            'working_days' => 15,
            'non_working_days' => 0,
            'paid_amount' => 0,
            'confirm_regenerate' => 1,
        ]);

        $fifteenDays = $employee->payrolls()->orderByDesc('id')->first();

        $this->assertSame(2500.05, (float) $fifteenDays->payable_salary);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'paid_amount' => 0,
            'confirm_regenerate' => 1,
        ]);

        $thirtyDays = $employee->payrolls()->orderByDesc('id')->first();

        $this->assertSame(5000.0, (float) $thirtyDays->payable_salary);
    }

    public function test_date_range_salary_defaults_working_days_inclusively_when_not_sent(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-06',
            'non_working_days' => 0,
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame(6.0, (float) $payroll->working_days);
        $this->assertSame(6000.0, (float) $payroll->payable_salary);
    }

    public function test_date_range_salary_uses_date_wise_non_working_adjustments(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);

        $adjustments = collect(range(1, 10))
            ->mapWithKeys(function (int $day) {
                $date = '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                $isNonWorking = in_array($day, [3, 7], true);

                return [
                    $day => [
                        'date' => $date,
                        'day_type' => $isNonWorking ? 'non_working' : 'working',
                        'reason' => match ($day) {
                            3 => 'client_issue',
                            7 => 'boosting_off',
                            default => 'active_working',
                        },
                        'note' => $isNonWorking ? 'Office note ' . $day : '',
                    ],
                ];
            })
            ->values()
            ->all();

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'salary_day_adjustments' => $adjustments,
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $payroll->refresh();

        $this->assertSame(8.0, (float) $payroll->working_days);
        $this->assertSame(2.0, (float) $payroll->non_working_days);
        $this->assertSame(8000.0, (float) $payroll->payable_salary);
        $this->assertCount(10, $payroll->salary_day_adjustments);
        $this->assertSame('client_issue', $payroll->salary_day_adjustments[2]['reason']);
        $this->assertSame('boosting_off', $payroll->salary_day_adjustments[6]['reason']);

        $showResponse = $this->actingAs($admin)->get('/admin/payroll/' . $payroll->id);

        $showResponse->assertOk();
        $showResponse->assertSee('Date-wise Adjustment');
        $showResponse->assertSee('Client Issue');
        $showResponse->assertSee('Boosting Off');
    }

    public function test_monthly_cycle_salary_defaults_working_days_to_fixed_thirty_days_without_salary_days(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 28000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'monthly_cycle',
            'salary_month' => '2026-02',
            'working_days' => null,
            'non_working_days' => null,
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame(30.0, (float) $payroll->working_days);
        $this->assertSame(30, $payroll->month_days);
        $this->assertSame(28000.0, (float) $payroll->payable_salary);
    }

    public function test_monthly_cycle_salary_uses_fixed_thirty_days_for_leap_year_february(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 29000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'monthly_cycle',
            'salary_month' => '2028-02',
            'working_days' => null,
            'non_working_days' => null,
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame(30.0, (float) $payroll->working_days);
        $this->assertSame(30, $payroll->month_days);
        $this->assertSame(29000.0, (float) $payroll->payable_salary);
    }

    public function test_admin_can_create_upcoming_salary_without_payment_details(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 10000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame('upcoming', $payroll->calculated_status);
        $this->assertSame(10000.0, (float) $payroll->payable_salary);
        $this->assertDatabaseHas('employee_payrolls', [
            'id' => $payroll->id,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'payment_method' => null,
            'payment_date' => null,
        ]);
    }

    public function test_admin_can_mark_upcoming_salary_paid_with_payment_proof(): void
    {
        Storage::fake('public');

        $admin = $this->user('admin');
        $employee = $this->employee();
        $payroll = $employee->payrolls()->create([
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-30',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'salary_month' => '2026-06-01',
            'payable_salary' => 30000,
            'paid_amount' => 0,
            'payment_status' => 'upcoming',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/update', [
            'payment_status' => 'paid',
            'paid_amount' => 30000,
            'payment_method' => 'Bank Transfer',
            'payment_date' => '2026-06-30',
            'transaction_id' => 'TXN-123',
            'payment_proof' => UploadedFile::fake()->image('salary-proof.jpg'),
            'note' => 'Salary paid',
        ]);

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $payroll->refresh();

        $this->assertSame('paid', $payroll->calculated_status);
        $this->assertSame('TXN-123', $payroll->transaction_id);
        $this->assertNotNull($payroll->payment_proof);
        Storage::disk('public')->assertExists($payroll->payment_proof);
    }

    public function test_paid_salary_status_requires_payment_details(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'payment_status' => 'paid',
            'paid_amount' => 30000,
        ]);

        $response->assertSessionHasErrors(['payment_method', 'payment_date']);
    }

    public function test_admin_can_generate_monthly_cycle_salary_without_requiring_salary_days(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);
        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'monthly_cycle',
            'salary_month' => '2026-06',
            'working_days' => null,
            'non_working_days' => null,
            'paid_amount' => 0,
            'payment_method' => null,
            'payment_date' => null,
            'note' => 'Monthly cycle salary',
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame('monthly_cycle', $payroll->calculation_type);
        $this->assertSame('2026-06-01', $payroll->salary_period_from->toDateString());
        $this->assertSame('2026-06-30', $payroll->salary_period_to->toDateString());
        $this->assertSame(30.0, (float) $payroll->working_days);
        $this->assertSame(0.0, (float) $payroll->non_working_days);
        $this->assertSame(30000.0, (float) $payroll->payable_salary);
        $this->assertSame('upcoming', $payroll->calculated_status);
    }

    public function test_salary_generate_pages_show_date_range_and_due_status(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Range Salary Employee',
            'monthly_salary' => 30000,
        ]);
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'payment_method' => 'bKash',
            'payment_date' => '2026-06-10',
            'status' => 'partial',
        ]);

        $listResponse = $this->actingAs($admin)->get('/admin/payroll');
        $showResponse = $this->actingAs($admin)->get('/admin/payroll/' . $payroll->id);

        $listResponse->assertOk();
        $listResponse->assertSee('/admin/employees/' . $employee->id, false);
        $listResponse->assertSee('2026-06-01 to 2026-06-10');
        $listResponse->assertSee('Partially Paid');
        $showResponse->assertOk();
        $showResponse->assertSee('Date To Date');
        $showResponse->assertSee('Salary Period');
        $showResponse->assertSee('2026-06-01 to 2026-06-10');
        $showResponse->assertSee('Daily Salary');
        $showResponse->assertSee('BDT 5,000.00');
    }

    public function test_admin_can_delete_salary_record_without_deleting_employee_or_client(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Delete Salary Employee',
        ]);
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/delete');

        $response->assertRedirect('/admin/payroll');
        $response->assertSessionHas('success', 'Salary record deleted successfully.');
        $this->assertDatabaseMissing('employee_payrolls', [
            'id' => $payroll->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_employee_dashboard_salary_history_shows_date_range(): void
    {
        $employeeUser = $this->user('employee');
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
        ]);
        $employee->payrolls()->create([
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 10000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($employeeUser)->get('/employee/salary');

        $response->assertOk();
        $response->assertSee('Salary Period');
        $response->assertSee('2026-06-01 to 2026-06-10');
        $response->assertSee('Paid');
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Date Range Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 30000,
        ], $overrides));
    }

    private function client(): Client
    {
        $clientUser = $this->user('client');

        return Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Date Range Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
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
