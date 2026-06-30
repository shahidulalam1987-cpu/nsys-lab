<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\Shift;
use App\Models\User;
use App\Services\AssignmentResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessWorkflowHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_employee_requires_confirmation_and_valid_lifecycle_dates(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/employees', [
            'name' => 'Lifecycle Employee',
            'mobile' => '01700000000',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-10',
            'confirmation_date' => null,
            'last_working_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);

        $response->assertSessionHasErrors('last_working_date');
        $this->assertDatabaseMissing('employees', ['name' => 'Lifecycle Employee']);

        $this->actingAs($this->admin())->post('/admin/employees', [
            'name' => 'Unconfirmed Active Employee',
            'mobile' => '01700000001',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-10',
            'confirmation_date' => null,
            'status' => 'active',
            'monthly_salary' => 10000,
        ])->assertSessionHasErrors('confirmation_date');
    }

    public function test_assignment_resolver_ignores_expired_active_records(): void
    {
        $employee = $this->employee();
        $expiredClient = $this->client(['company_name' => 'Expired Client']);
        $currentClient = $this->client(['company_name' => 'Current Client']);
        $employee->assignments()->create([
            'client_id' => $expiredClient->id,
            'assigned_from' => '2026-05-01',
            'assigned_to' => '2026-05-31',
            'status' => 'active',
        ]);
        $employee->assignments()->create([
            'client_id' => $currentClient->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);

        $resolved = app(AssignmentResolver::class)->current($employee, Carbon::parse('2026-06-15'));

        $this->assertSame($currentClient->id, $resolved?->client_id);
    }

    public function test_assignment_rejects_page_from_another_client(): void
    {
        $client = $this->client();
        $otherClient = $this->client(['company_name' => 'Other Client']);
        $page = ClientPage::create([
            'client_id' => $otherClient->id,
            'page_name' => 'Wrong Client Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin())->post('/admin/assignments', [
            'employee_id' => $this->employee()->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'shift_id' => Shift::query()->firstOrFail()->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ])->assertSessionHasErrors('client_page_id');

        $this->assertDatabaseCount('employee_assignments', 0);
    }

    public function test_work_status_before_confirmation_is_blocked(): void
    {
        $employee = $this->employee(['confirmation_date' => '2026-06-10']);

        $this->actingAs($this->admin())->post('/admin/work-status', [
            'employee_id' => $employee->id,
            'work_date' => '2026-06-09',
            'status' => 'working',
        ])->assertSessionHasErrors('work_date');

        $this->assertDatabaseCount('employee_work_statuses', 0);
    }

    public function test_client_with_payroll_history_cannot_be_deleted(): void
    {
        $client = $this->client();
        $employee = $this->employee();
        EmployeePayroll::create([
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 1000,
            'paid_amount' => 0,
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/clients/'.$client->id.'/delete')
            ->assertSessionHasErrors('client');

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function client(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'company_name' => 'Hardening Client '.uniqid(),
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ], $overrides));
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'HARD-'.uniqid(),
            'name' => 'Hardening Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-08',
            'status' => 'active',
            'monthly_salary' => 10000,
        ], $overrides));
    }
}
