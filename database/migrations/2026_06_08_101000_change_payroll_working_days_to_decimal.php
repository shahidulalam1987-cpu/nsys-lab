<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE employee_payrolls MODIFY working_days DECIMAL(5,2) NULL');
            DB::statement('ALTER TABLE employee_payrolls MODIFY non_working_days DECIMAL(5,2) NULL');

            return;
        }

        $this->rebuildSqliteTable('DECIMAL(5,2)', 'DECIMAL(5,2)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE employee_payrolls MODIFY working_days UNSIGNED SMALLINT NULL');
            DB::statement('ALTER TABLE employee_payrolls MODIFY non_working_days UNSIGNED SMALLINT NULL');

            return;
        }

        $this->rebuildSqliteTable('INTEGER', 'INTEGER');
    }

    private function rebuildSqliteTable(string $workingDaysType, string $nonWorkingDaysType): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement(<<<SQL
            CREATE TABLE employee_payrolls_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                employee_id INTEGER NOT NULL,
                client_id INTEGER NULL,
                calculation_type VARCHAR NOT NULL DEFAULT 'date_to_date',
                salary_period_from DATE NULL,
                salary_period_to DATE NULL,
                from_date DATE NULL,
                to_date DATE NULL,
                working_days {$workingDaysType} NULL,
                non_working_days {$nonWorkingDaysType} NULL,
                salary_month DATE NOT NULL,
                payable_salary NUMERIC NOT NULL DEFAULT 0,
                paid_amount NUMERIC NOT NULL DEFAULT 0,
                payment_method VARCHAR NULL,
                payment_date DATE NULL,
                status VARCHAR NOT NULL DEFAULT 'unpaid',
                payment_status VARCHAR NULL,
                payment_proof VARCHAR NULL,
                transaction_id VARCHAR NULL,
                note TEXT NULL,
                month_days INTEGER NULL,
                daily_salary NUMERIC NULL,
                salary_day_adjustments TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL
            )
        SQL);

        DB::statement(<<<SQL
            INSERT INTO employee_payrolls_new (
                id, employee_id, client_id, calculation_type, salary_period_from, salary_period_to,
                from_date, to_date, working_days, non_working_days, salary_month, payable_salary,
                paid_amount, payment_method, payment_date, status, payment_status, payment_proof,
                transaction_id, note, month_days, daily_salary, salary_day_adjustments, created_at, updated_at
            )
            SELECT
                id, employee_id, client_id, calculation_type, salary_period_from, salary_period_to,
                from_date, to_date, working_days, non_working_days, salary_month, payable_salary,
                paid_amount, payment_method, payment_date, status, payment_status, payment_proof,
                transaction_id, note, month_days, daily_salary, salary_day_adjustments, created_at, updated_at
            FROM employee_payrolls
        SQL);

        DB::statement('DROP TABLE employee_payrolls');
        DB::statement('ALTER TABLE employee_payrolls_new RENAME TO employee_payrolls');
        DB::statement('CREATE INDEX employee_payrolls_salary_month_status_index ON employee_payrolls (salary_month, status)');
        DB::statement('CREATE INDEX employee_payrolls_employee_id_salary_month_index ON employee_payrolls (employee_id, salary_month)');
        DB::statement('CREATE INDEX employee_payrolls_client_id_salary_month_index ON employee_payrolls (client_id, salary_month)');
        DB::statement('CREATE INDEX employee_payrolls_from_date_to_date_index ON employee_payrolls (from_date, to_date)');
        DB::statement('CREATE INDEX employee_payrolls_calc_period_index ON employee_payrolls (calculation_type, salary_period_from, salary_period_to)');

        Schema::enableForeignKeyConstraints();
    }
};
