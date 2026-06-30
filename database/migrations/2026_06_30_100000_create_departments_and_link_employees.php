<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const DEFAULT_DEPARTMENTS = [
        'Management',
        'Administration',
        'HR',
        'Finance',
        'Sales',
        'Customer Care',
        'Support',
        'Facebook Operations',
        'TikTok Operations',
        'Courier Operations',
        'Creative',
        'Design',
        'Development',
        'Moderator',
        'Client Department',
        'Employee Department',
    ];

    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('department')->constrained('departments')->nullOnDelete();
        });

        $now = now();
        $names = collect(self::DEFAULT_DEPARTMENTS)
            ->merge(DB::table('employees')->whereNotNull('department')->pluck('department'))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        foreach ($names as $index => $name) {
            DB::table('departments')->insert([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'status' => 'active',
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('employees')->whereNotNull('department')->orderBy('id')->each(function ($employee) {
            $departmentId = DB::table('departments')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $employee->department))])
                ->value('id');

            if ($departmentId) {
                DB::table('employees')->where('id', $employee->id)->update(['department_id' => $departmentId]);
            }
        });

        $this->addPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')->whereIn('key', ['departments.view', 'departments.manage'])->pluck('id');
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::dropIfExists('departments');
    }

    private function addPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        foreach ([
            'departments.view' => 'Departments View',
            'departments.manage' => 'Departments Manage',
        ] as $key => $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'key' => $key,
                'module' => 'employees',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('key', ['departments.view', 'departments.manage'])->pluck('id', 'key');
        $roleIds = DB::table('roles')->whereIn('slug', ['super_admin', 'hr_manager'])->pluck('id', 'slug');

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
        $base = Str::slug($name) ?: 'department';
        $slug = $base;
        $suffix = 2;

        while (DB::table('departments')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
};
