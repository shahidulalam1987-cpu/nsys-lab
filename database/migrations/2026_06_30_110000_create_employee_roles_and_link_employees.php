<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const DEFAULT_ROLES = [
        'Admin' => 'Administration',
        'Manager' => 'Management',
        'Team Leader' => 'Management',
        'HR' => 'HR',
        'Finance Officer' => 'Finance',
        'Developer' => 'Development',
        'Designer' => 'Design',
        'Trainee Moderator' => 'Moderator',
        'Moderator' => 'Moderator',
        'Senior Moderator' => 'Moderator',
        'Customer Care' => 'Customer Care',
        'Sales Executive' => 'Sales',
        'Graphic Designer' => 'Design',
        'Video Editor' => 'Creative',
        'Support' => 'Support',
        'Courier Officer' => 'Courier Operations',
        'Courier Manager' => 'Courier Operations',
        'Delivery Follow-up Officer' => 'Courier Operations',
        'Return Management Officer' => 'Courier Operations',
        'Custom' => null,
    ];

    public function up(): void
    {
        Schema::create('employee_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('employee_roles')->nullOnDelete();
        });

        $now = now();
        $names = collect(array_keys(self::DEFAULT_ROLES))
            ->merge(DB::table('employees')->whereNotNull('role')->pluck('role'))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        foreach ($names as $index => $name) {
            $departmentName = self::DEFAULT_ROLES[$name] ?? null;
            DB::table('employee_roles')->insert([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'department_id' => $departmentName
                    ? DB::table('departments')->where('name', $departmentName)->value('id')
                    : null,
                'status' => 'active',
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('employees')->whereNotNull('role')->orderBy('id')->each(function ($employee) {
            $roleId = DB::table('employee_roles')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $employee->role))])
                ->value('id');

            if ($roleId) {
                DB::table('employees')->where('id', $employee->id)->update(['role_id' => $roleId]);
            }
        });

        $this->addPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')->whereIn('key', ['employee_roles.view', 'employee_roles.manage'])->pluck('id');
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('employee_roles');
    }

    private function addPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        foreach ([
            'employee_roles.view' => 'Employee Roles View',
            'employee_roles.manage' => 'Employee Roles Manage',
        ] as $key => $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'key' => $key,
                'module' => 'employees',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('key', ['employee_roles.view', 'employee_roles.manage'])->pluck('id');
        $roleIds = DB::table('roles')->whereIn('slug', ['super_admin', 'hr_manager'])->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'employee-role';
        $slug = $base;
        $suffix = 2;

        while (DB::table('employee_roles')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
};
