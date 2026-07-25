<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePhase5PageShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_client_payment_history_record(): void
    {
        $admin = $this->user('admin');
        $payment = SalaryPayment::create([
            'client_id' => $this->client()->id,
            'salary_month' => '2026-06-15',
            'amount' => 12000,
            'payment_method' => 'Bank',
            'transaction_id' => 'DELETE-FUND',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $history = $this->actingAs($admin)->get('/admin/salary-payments');
        $history->assertOk();
        $history->assertSee('Delete this client payment record?', false);

        $response = $this->actingAs($admin)->post('/admin/salary-payments/' . $payment->id . '/delete');

        $response->assertRedirect('/admin/salary-payments');
        $response->assertSessionHas('success', 'Client payment record deleted successfully.');
        $this->assertDatabaseMissing('salary_payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('clients', ['id' => $payment->client_id]);
    }

    public function test_admin_can_manage_client_pages_and_assign_employee_to_page_shift(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $shift = Shift::where('name', 'Full Day Shift')->firstOrFail();

        $createPage = $this->actingAs($admin)->post('/admin/client-pages', [
            'client_id' => $client->id,
            'page_name' => 'NSYS Main Page',
            'page_url' => 'https://example.com/nsys-main',
            'platform' => 'Facebook',
            'status' => 'active',
            'note' => 'Main client page',
        ]);
        $page = ClientPage::first();

        $createPage->assertRedirect('/admin/client-pages');
        $this->assertDatabaseHas('client_pages', [
            'id' => $page->id,
            'page_name' => 'NSYS Main Page',
        ]);

        $assign = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/assignments', [
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
            'note' => 'Page shift assignment',
        ]);

        $assign->assertSessionHas('success');
        $this->assertDatabaseHas('employee_assignments', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
        ]);

        $profile = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);
        $profile->assertOk();
        $profile->assertSee('Assign to Client/Page/Shift');
        $profile->assertSee('NSYS Main Page');
        $profile->assertSee('Full Day Shift');
    }

    public function test_assignment_management_can_create_view_edit_and_blocks_duplicate_active_page(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $shift = Shift::where('name', 'Night Shift')->firstOrFail();
        $page = ClientPage::create([
            'client_id' => $client->id,
            'page_name' => 'Assignment Management Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);

        $createPage = $this->actingAs($admin)->get('/admin/assignments/create');
        $createPage->assertOk();
        $createPage->assertSee('Filtered automatically by selected client.');
        $createPage->assertSee('Assignment Management Page');

        $response = $this->actingAs($admin)->post('/admin/assignments', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
            'note' => 'Standalone assignment',
        ]);
        $assignment = $employee->assignments()->first();

        $response->assertRedirect('/admin/assignments');
        $this->assertDatabaseHas('employee_assignments', [
            'employee_id' => $employee->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'status' => 'active',
        ]);

        $list = $this->actingAs($admin)->get('/admin/assignments');
        $list->assertOk();
        $list->assertSee('Assignment Dashboard');
        $list->assertSee('Total Assignments');
        $list->assertSee('Active Assignments');
        $list->assertSee('Assignment Management Page');
        $list->assertSee('Night Shift');

        $show = $this->actingAs($admin)->get('/admin/assignments/' . $assignment->id);
        $show->assertOk();
        $show->assertSee('Assignment Details');
        $show->assertSee('Standalone assignment');

        $duplicate = $this->actingAs($admin)->from('/admin/assignments/create')->post('/admin/assignments', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'assigned_from' => '2026-06-02',
            'status' => 'active',
        ]);

        $duplicate->assertRedirect('/admin/assignments/create');
        $duplicate->assertSessionHasErrors('client_page_id');

        $update = $this->actingAs($admin)->post('/admin/assignments/' . $assignment->id . '/update', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'assigned_from' => '2026-06-01',
            'assigned_to' => '2026-06-10',
            'status' => 'ended',
            'note' => 'Completed assignment',
        ]);

        $update->assertRedirect('/admin/assignments');
        $this->assertSame('ended', $assignment->fresh()->status);

        $remove = $this->actingAs($admin)->post('/admin/assignments/' . $assignment->id . '/remove');
        $remove->assertRedirect('/admin/assignments');
        $this->assertDatabaseMissing('employee_assignments', ['id' => $assignment->id]);
    }

    public function test_default_nsys_shift_options_use_correct_times(): void
    {
        $morning = Shift::where('name', 'Morning Shift')->firstOrFail();
        $night = Shift::where('name', 'Night Shift')->firstOrFail();
        $fullDay = Shift::where('name', 'Full Day Shift')->firstOrFail();

        $this->assertSame('09:00 AM - 05:00 PM', $morning->timeRange());
        $this->assertSame('05:00 PM - 01:00 AM', $night->timeRange());
        $this->assertSame('09:00 AM - 01:00 AM', $fullDay->timeRange());
    }

    public function test_work_status_accepts_page_and_shift_and_employee_dashboard_shows_them(): void
    {
        $admin = $this->user('admin');
        $employeeUser = $this->user('employee');
        $client = $this->client();
        $page = ClientPage::create([
            'client_id' => $client->id,
            'page_name' => 'Work Status Page',
            'platform' => 'Instagram',
            'status' => 'active',
        ]);
        $shift = Shift::where('name', 'Morning Shift')->firstOrFail();
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
            'shift_id' => $shift->id,
        ]);
        $employee->assignments()->create([
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'assigned_from' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/admin/work-status', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
            'work_date' => today()->toDateString(),
            'status' => 'working',
            'note' => 'Assigned work',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employee_work_statuses', [
            'employee_id' => $employee->id,
            'client_page_id' => $page->id,
            'shift_id' => $shift->id,
        ]);

        $dashboard = $this->actingAs($employeeUser)->get('/employee/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('Assigned Client');
        $dashboard->assertSee('Work Status Page');
        $dashboard->assertSee('Morning Shift');
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
            'name' => 'Phase Five Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 30000,
        ], $overrides));
    }

    private function client(): Client
    {
        $clientUser = $this->user('client');

        return Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Phase Five Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
    }
}
