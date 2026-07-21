<?php

namespace Tests\Feature;

use App\Models\EmployeeNotice;
use App\Models\EmployeeNoticeRead;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionRouteRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EmployeeNoticeBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_notice_board_uses_dedicated_notice_permissions(): void
    {
        $registry = app(PermissionRouteRegistry::class);

        $this->assertSame(['notices.view'], $registry->permissionsFor(Request::create('/admin/employee-notices', 'GET')));
        $this->assertSame(['notices.manage'], $registry->permissionsFor(Request::create('/admin/employee-notices', 'POST')));
        $this->assertSame(['notices.manage'], $registry->permissionsFor(Request::create('/admin/employee-notices/1/delete', 'POST')));
    }

    public function test_hr_can_manage_notices_and_actions_are_logged(): void
    {
        $hr = $this->staff('hr_manager');

        $this->actingAs($hr)
            ->post('/admin/employee-notices', [
                'title' => 'Salary Processing Notice',
                'category' => 'salary',
                'description' => 'Salary will be processed this week.',
            ])
            ->assertRedirect('/admin/employee-notices');

        $notice = EmployeeNotice::where('title', 'Salary Processing Notice')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Notice Board',
            'action' => 'Notice Created',
        ]);

        $this->actingAs($hr)
            ->get('/admin/employee-notices?category=salary&search=Processing')
            ->assertOk()
            ->assertSee('Salary Processing Notice')
            ->assertSee('Salary Notices')
            ->assertSee('Total Reads');

        $this->actingAs($hr)
            ->post('/admin/employee-notices/' . $notice->id . '/update', [
                'title' => 'Updated Salary Notice',
                'category' => 'salary',
                'description' => 'Updated salary processing note.',
            ])
            ->assertRedirect('/admin/employee-notices');

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Notice Board',
            'action' => 'Notice Updated',
        ]);

        $this->actingAs($hr)
            ->post('/admin/employee-notices/' . $notice->id . '/delete')
            ->assertRedirect('/admin/employee-notices');

        $this->assertDatabaseMissing('employee_notices', ['id' => $notice->id]);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Notice Board',
            'action' => 'Notice Deleted',
        ]);
    }

    public function test_non_notice_manager_cannot_manage_notice_board(): void
    {
        $finance = $this->staff('finance_manager');

        $this->actingAs($finance)->get('/admin/employee-notices')->assertForbidden();
        $this->actingAs($finance)
            ->post('/admin/employee-notices', [
                'title' => 'Blocked Notice',
                'category' => 'general',
                'description' => 'Finance should not manage employee notices.',
            ])
            ->assertForbidden();
    }

    public function test_notice_read_count_appears_on_admin_list(): void
    {
        $hr = $this->staff('hr_manager');
        $employeeUser = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_id' => 'NOTICE-' . uniqid(),
            'name' => 'Notice Reader',
            'department' => 'Operations',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);
        $notice = EmployeeNotice::create([
            'title' => 'Emergency Notice',
            'category' => 'emergency',
            'description' => 'Emergency notice for employees.',
            'published_at' => now(),
        ]);

        EmployeeNoticeRead::create([
            'employee_notice_id' => $notice->id,
            'employee_id' => $employee->id,
            'read_at' => now(),
        ]);

        $this->actingAs($hr)
            ->get('/admin/employee-notices?category=emergency')
            ->assertOk()
            ->assertSee('Emergency Notice')
            ->assertSee('1 reads');
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user->roles()->sync([Role::where('slug', $role)->valueOrFail('id')]);

        return $user->fresh();
    }
}
