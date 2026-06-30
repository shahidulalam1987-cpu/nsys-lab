<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\Role as PermissionRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_and_courier_roles_are_seeded(): void
    {
        $this->assertSame(20, EmployeeRole::count());
        $this->assertSame(4, EmployeeRole::whereIn('name', [
            'Courier Officer',
            'Courier Manager',
            'Delivery Follow-up Officer',
            'Return Management Officer',
        ])->count());
        $this->assertSame(
            'Courier Operations',
            EmployeeRole::where('name', 'Courier Officer')->firstOrFail()->department?->name
        );
    }

    public function test_employee_create_uses_role_id_and_keeps_legacy_name(): void
    {
        $role = EmployeeRole::where('name', 'Courier Officer')->firstOrFail();

        $this->actingAs($this->superAdmin())->post('/admin/employees', $this->employeePayload([
            'name' => 'Courier Role Employee',
            'department_id' => Department::where('name', 'Courier Operations')->valueOrFail('id'),
            'role_id' => $role->id,
        ]))->assertRedirect('/admin/employees');

        $this->assertDatabaseHas('employees', [
            'name' => 'Courier Role Employee',
            'role_id' => $role->id,
            'role' => 'Courier Officer',
        ]);
    }

    public function test_legacy_role_payload_is_linked_and_role_name_falls_back(): void
    {
        $payload = $this->employeePayload();
        unset($payload['role_id']);
        $payload['role'] = 'Moderator';

        $this->actingAs($this->superAdmin())->post('/admin/employees', $payload)->assertRedirect('/admin/employees');
        $linked = Employee::where('name', $payload['name'])->firstOrFail();
        $this->assertNotNull($linked->role_id);
        $this->assertSame('Moderator', $linked->roleName());

        $legacy = Employee::create($this->employeeModelData([
            'employee_id' => 'ROLE-FALLBACK',
            'role_id' => null,
            'role' => 'Historical Role',
        ]));
        $this->assertSame('Historical Role', $legacy->roleName());
    }

    public function test_inactive_role_is_hidden_for_create_but_visible_for_current_employee_edit(): void
    {
        $role = EmployeeRole::create([
            'name' => 'Archived Role',
            'slug' => 'archived-role',
            'status' => 'inactive',
            'sort_order' => 99,
        ]);
        $employee = Employee::create($this->employeeModelData([
            'role_id' => $role->id,
            'role' => $role->name,
        ]));
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get('/admin/employees/create')->assertOk()->assertDontSee('Archived Role');
        $this->actingAs($admin)->get('/admin/employees/'.$employee->id.'/edit')
            ->assertOk()
            ->assertSee('Archived Role')
            ->assertSee('(Inactive)');
    }

    public function test_assigned_role_cannot_be_deleted_and_empty_role_soft_deletes(): void
    {
        $assigned = EmployeeRole::where('name', 'Moderator')->firstOrFail();
        Employee::create($this->employeeModelData(['role_id' => $assigned->id]));
        $admin = $this->superAdmin();

        $this->actingAs($admin)->delete('/admin/employee-roles/'.$assigned->id)->assertSessionHasErrors('role');
        $this->assertNotSoftDeleted('employee_roles', ['id' => $assigned->id]);

        $empty = EmployeeRole::create(['name' => 'Temporary Role', 'slug' => 'temporary-role', 'status' => 'active']);
        $this->actingAs($admin)->delete('/admin/employee-roles/'.$empty->id)->assertRedirect('/admin/employee-roles');
        $this->assertSoftDeleted('employee_roles', ['id' => $empty->id]);
    }

    public function test_hr_can_manage_roles_and_unauthorized_manager_cannot(): void
    {
        $hr = $this->staff('hr_manager');
        $this->actingAs($hr)->get('/admin/employee-roles')->assertOk();
        $this->actingAs($hr)->post('/admin/employee-roles', [
            'name' => 'Recruitment Officer',
            'department_id' => Department::where('name', 'HR')->valueOrFail('id'),
            'status' => 'active',
            'sort_order' => 25,
        ])->assertRedirect('/admin/employee-roles');
        $this->assertDatabaseHas('employee_roles', ['name' => 'Recruitment Officer']);

        $finance = $this->staff('finance_manager');
        $this->actingAs($finance)->get('/admin/employee-roles')->assertForbidden();
        $this->actingAs($finance)->post('/admin/employee-roles', [
            'name' => 'Unauthorized Role',
            'status' => 'active',
            'sort_order' => 26,
        ])->assertForbidden();
    }

    public function test_role_names_are_unique_ignoring_case(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/employee-roles', [
            'name' => 'moderator',
            'status' => 'active',
            'sort_order' => 30,
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, EmployeeRole::whereRaw('LOWER(name) = ?', ['moderator'])->count());
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user->roles()->sync([PermissionRole::where('slug', $role)->valueOrFail('id')]);

        return $user->fresh();
    }

    private function employeePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Role Employee',
            'mobile' => '01700000000',
            'employee_type' => 'client_assigned',
            'department_id' => Department::where('name', 'Moderator')->valueOrFail('id'),
            'role_id' => EmployeeRole::where('name', 'Moderator')->valueOrFail('id'),
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-08',
            'status' => 'active',
            'monthly_salary' => 10000,
        ], $overrides);
    }

    private function employeeModelData(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => 'ROLE-'.uniqid(),
            'name' => 'Role Employee',
            'department' => 'Moderator',
            'department_id' => Department::where('name', 'Moderator')->valueOrFail('id'),
            'role' => 'Moderator',
            'role_id' => EmployeeRole::where('name', 'Moderator')->valueOrFail('id'),
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-08',
            'status' => 'active',
            'monthly_salary' => 10000,
        ], $overrides);
    }
}
