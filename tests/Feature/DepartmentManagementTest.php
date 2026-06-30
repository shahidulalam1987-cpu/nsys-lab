<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_departments_and_courier_operations_are_seeded(): void
    {
        $this->assertSame(16, Department::count());
        $this->assertDatabaseHas('departments', [
            'name' => 'Courier Operations',
            'slug' => 'courier-operations',
            'status' => 'active',
        ]);
    }

    public function test_employee_create_uses_department_id_and_keeps_legacy_name(): void
    {
        $department = Department::where('name', 'Courier Operations')->firstOrFail();

        $this->actingAs($this->superAdmin())->post('/admin/employees', $this->employeePayload([
            'name' => 'Courier Employee',
            'department_id' => $department->id,
        ]))->assertRedirect('/admin/employees');

        $this->assertDatabaseHas('employees', [
            'name' => 'Courier Employee',
            'department_id' => $department->id,
            'department' => 'Courier Operations',
        ]);
    }

    public function test_legacy_department_name_payload_is_linked_and_helper_falls_back(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/employees', $this->employeePayload([
            'name' => 'Legacy Payload Employee',
            'department' => 'Moderator',
        ]))->assertRedirect('/admin/employees');

        $linked = Employee::where('name', 'Legacy Payload Employee')->firstOrFail();
        $this->assertNotNull($linked->department_id);
        $this->assertSame('Moderator', $linked->departmentName());

        $legacy = Employee::create($this->employeeModelData([
            'employee_id' => 'LEGACY-FALLBACK',
            'name' => 'Legacy Fallback',
            'department_id' => null,
            'department' => 'Historical Department',
        ]));
        $this->assertSame('Historical Department', $legacy->departmentName());
    }

    public function test_inactive_department_is_hidden_for_create_but_visible_for_current_employee_edit(): void
    {
        $department = Department::create([
            'name' => 'Archived Operations',
            'slug' => 'archived-operations',
            'status' => 'inactive',
            'sort_order' => 99,
        ]);
        $employee = Employee::create($this->employeeModelData([
            'department_id' => $department->id,
            'department' => $department->name,
        ]));
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get('/admin/employees/create')
            ->assertOk()
            ->assertDontSee('Archived Operations');
        $this->actingAs($admin)->get('/admin/employees/'.$employee->id.'/edit')
            ->assertOk()
            ->assertSee('Archived Operations')
            ->assertSee('(Inactive)');
    }

    public function test_department_with_employees_cannot_be_deleted(): void
    {
        $department = Department::where('name', 'Moderator')->firstOrFail();
        Employee::create($this->employeeModelData(['department_id' => $department->id]));

        $this->actingAs($this->superAdmin())
            ->delete('/admin/departments/'.$department->id)
            ->assertSessionHasErrors('department');

        $this->assertNotSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_empty_department_soft_deletes(): void
    {
        $department = Department::create([
            'name' => 'Temporary Department',
            'slug' => 'temporary-department',
            'status' => 'active',
        ]);

        $this->actingAs($this->superAdmin())
            ->delete('/admin/departments/'.$department->id)
            ->assertRedirect('/admin/departments');

        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_hr_manager_can_manage_departments_and_other_manager_cannot(): void
    {
        $hr = $this->staff('hr_manager');
        $this->actingAs($hr)->get('/admin/departments')->assertOk();
        $this->actingAs($hr)->post('/admin/departments', [
            'name' => 'Quality Assurance',
            'description' => 'QA team',
            'status' => 'active',
            'sort_order' => 20,
        ])->assertRedirect('/admin/departments');
        $this->assertDatabaseHas('departments', ['name' => 'Quality Assurance']);

        $finance = $this->staff('finance_manager');
        $this->actingAs($finance)->get('/admin/departments')->assertForbidden();
        $this->actingAs($finance)->post('/admin/departments', [
            'name' => 'Unauthorized Department',
            'status' => 'active',
            'sort_order' => 21,
        ])->assertForbidden();
    }

    public function test_department_names_are_unique_ignoring_case(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/departments', [
            'name' => 'moderator',
            'status' => 'active',
            'sort_order' => 30,
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, Department::whereRaw('LOWER(name) = ?', ['moderator'])->count());
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user->roles()->sync([Role::where('slug', $role)->valueOrFail('id')]);

        return $user->fresh();
    }

    private function employeePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Department Employee',
            'mobile' => '01700000000',
            'employee_type' => 'client_assigned',
            'department_id' => Department::where('name', 'Moderator')->valueOrFail('id'),
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-08',
            'status' => 'active',
            'monthly_salary' => 10000,
        ], $overrides);
    }

    private function employeeModelData(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => 'DEP-'.uniqid(),
            'name' => 'Department Employee',
            'department' => 'Moderator',
            'department_id' => Department::where('name', 'Moderator')->valueOrFail('id'),
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-08',
            'status' => 'active',
            'monthly_salary' => 10000,
        ], $overrides);
    }
}
