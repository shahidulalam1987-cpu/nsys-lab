<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_managers_are_limited_to_their_modules(): void
    {
        $finance = $this->staff('finance_manager');
        $this->actingAs($finance)->get('/admin/financial-management')->assertOk();
        $this->actingAs($finance)->get('/admin/client-fund')->assertOk();
        $this->actingAs($finance)->get('/admin/payroll')->assertForbidden();

        $hr = $this->staff('hr_manager');
        $this->actingAs($hr)->get('/admin/employees')->assertOk();
        $this->actingAs($hr)->get('/admin/payroll')->assertOk();
        $this->actingAs($hr)->get('/admin/financial-management')->assertForbidden();

        $facebook = $this->staff('facebook_manager');
        $this->actingAs($facebook)->get('/admin/facebook-dashboard')->assertOk();
        $this->actingAs($facebook)->get('/admin/bug-tracker')->assertForbidden();
    }

    public function test_moderator_can_access_own_operations_but_not_salary(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $this->assignRole($user, 'moderator');
        Employee::create([
            'user_id' => $user->id, 'employee_id' => 'MOD-001', 'name' => 'Assigned Moderator',
            'department' => 'Moderator', 'role' => 'Moderator', 'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-08', 'status' => 'active', 'monthly_salary' => 5000,
        ]);

        $this->actingAs($user)->get('/admin/work-status')->assertOk();
        $this->actingAs($user)->get('/admin/daily-reports')->assertOk();
        $this->actingAs($user)->get('/admin/payroll')->assertForbidden();
        $this->actingAs($user)->get('/admin/employees')->assertForbidden();
    }

    public function test_client_and_employee_logins_remain_isolated(): void
    {
        $client = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $employee = User::factory()->create(['role' => 'employee', 'status' => 'active']);

        $this->actingAs($client)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($employee)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($client)->get('/employee/dashboard')->assertForbidden();
        $this->actingAs($employee)->get('/client/dashboard')->assertForbidden();
    }

    public function test_navigation_hides_unauthorized_departments(): void
    {
        $response = $this->actingAs($this->staff('finance_manager'))->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Finance Manager Dashboard');
        $response->assertSee('href="/admin/financial-management"', false);
        $response->assertDontSee('href="/admin/employee-dashboard"', false);
        $response->assertDontSee('href="/admin/facebook-dashboard"', false);
        $response->assertDontSee('href="/admin/bug-tracker"', false);
    }

    public function test_legacy_admin_and_activity_audit_remain_compatible(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->assertTrue($admin->isSuperAdmin());
        $this->actingAs($admin)->get('/admin/bug-tracker')->assertOk();

        $hr = $this->staff('hr_manager');
        $this->actingAs($hr);
        app(ActivityLogger::class)->log('Employees', 'Employee Updated', 'Role audit test.', null, ['status' => 'probation'], ['status' => 'active']);

        $log = \App\Models\ActivityLog::firstOrFail();
        $this->assertSame('HR Manager', $log->role_name);
        $this->assertSame(['status' => 'probation'], $log->old_value);
        $this->assertSame(['status' => 'active'], $log->new_value);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->assignRole($user, $role);

        return $user->fresh();
    }

    private function assignRole(User $user, string $role): void
    {
        $user->roles()->sync([Role::where('slug', $role)->valueOrFail('id')]);
    }
}
