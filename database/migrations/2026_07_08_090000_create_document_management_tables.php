<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('General');
            $table->json('tags')->nullable();
            $table->string('owner_module')->nullable();
            $table->string('owner_record_type')->nullable();
            $table->unsignedBigInteger('owner_record_id')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('active');
            $table->date('expiry_date')->nullable();
            $table->string('current_file_path');
            $table->string('original_file_name')->nullable();
            $table->timestamps();

            $table->index(['owner_module', 'owner_record_id']);
            $table->index(['owner_record_type', 'owner_record_id']);
            $table->index(['category', 'status']);
            $table->index('expiry_date');
        });

        Schema::create('managed_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_document_id')->constrained('managed_documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('file_path');
            $table->string('original_file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_note')->nullable();
            $table->timestamps();

            $table->unique(['managed_document_id', 'version']);
        });

        Schema::create('managed_document_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_document_id')->nullable()->constrained('managed_documents')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });

        if (Schema::hasTable('permissions')) {
            $now = now();
            $permissions = [
                'documents.view' => ['Documents View', 'documents', 'View enterprise documents.'],
                'documents.manage' => ['Documents Manage', 'documents', 'Upload, update, archive, and restore enterprise documents.'],
            ];

            foreach ($permissions as $key => [$name, $module, $description]) {
                DB::table('permissions')->updateOrInsert(
                    ['key' => $key],
                    compact('name', 'key', 'module', 'description') + ['updated_at' => $now, 'created_at' => $now]
                );
            }

            $permissionIds = DB::table('permissions')->whereIn('key', array_keys($permissions))->pluck('id', 'key');
            $roleIds = DB::table('roles')->whereIn('slug', ['super_admin'])->pluck('id', 'slug');

            foreach ($roleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_document_audits');
        Schema::dropIfExists('managed_document_versions');
        Schema::dropIfExists('managed_documents');
    }
};
