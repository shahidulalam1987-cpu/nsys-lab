<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteUsersTable("CHECK (role IN ('admin', 'client', 'employee'))");

            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'client', 'employee') NOT NULL DEFAULT 'client'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'employee')->update(['role' => 'client']);

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteUsersTable("CHECK (role IN ('admin', 'client'))");

            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'client') NOT NULL DEFAULT 'client'");
    }

    private function rebuildSqliteUsersTable(string $roleCheck): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');
        DB::statement('DROP TABLE IF EXISTS users_role_rebuild');

        DB::statement(<<<SQL
CREATE TABLE users_role_rebuild (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name VARCHAR NOT NULL,
    email VARCHAR NOT NULL,
    role VARCHAR NOT NULL DEFAULT 'client' {$roleCheck},
    status VARCHAR NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    password VARCHAR NOT NULL,
    remember_token VARCHAR NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
)
SQL);

        DB::statement(<<<SQL
INSERT INTO users_role_rebuild (
    id,
    name,
    email,
    role,
    status,
    email_verified_at,
    password,
    remember_token,
    created_at,
    updated_at
)
SELECT
    id,
    name,
    email,
    role,
    COALESCE(status, 'active'),
    email_verified_at,
    password,
    remember_token,
    created_at,
    updated_at
FROM users
SQL);

        DB::statement('DROP TABLE users');
        DB::statement('ALTER TABLE users_role_rebuild RENAME TO users');
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
