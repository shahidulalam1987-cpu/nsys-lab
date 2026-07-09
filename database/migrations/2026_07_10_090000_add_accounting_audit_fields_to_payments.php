<?php

use App\Models\EmployeePayroll;
use App\Models\SalaryPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_payments', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('salary_payments', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('salary_payments', 'approved_ip')) {
                $table->string('approved_ip')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('salary_payments', 'approved_user_agent')) {
                $table->text('approved_user_agent')->nullable()->after('approved_ip');
            }
        });

        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_payrolls', 'salary_receipt_number')) {
                $table->string('salary_receipt_number')->nullable()->unique()->after('id');
            }
        });

        SalaryPayment::query()
            ->whereNull('receipt_number')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function (SalaryPayment $payment) {
                DB::table('salary_payments')
                    ->where('id', $payment->id)
                    ->update(['receipt_number' => $this->receipt('CP', (int) $payment->id, $payment->created_at)]);
            });

        EmployeePayroll::query()
            ->whereNotNull('paid_amount')
            ->where('paid_amount', '>', 0)
            ->whereNull('salary_receipt_number')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function (EmployeePayroll $payroll) {
                DB::table('employee_payrolls')
                    ->where('id', $payroll->id)
                    ->update(['salary_receipt_number' => $this->receipt('SP', (int) $payroll->id, $payroll->created_at)]);
            });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            foreach (['approved_by', 'approved_ip', 'approved_user_agent', 'receipt_number'] as $column) {
                if (Schema::hasColumn('salary_payments', $column)) {
                    $column === 'approved_by'
                        ? $table->dropConstrainedForeignId($column)
                        : $table->dropColumn($column);
                }
            }
        });

        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('employee_payrolls', 'salary_receipt_number')) {
                $table->dropColumn('salary_receipt_number');
            }
        });
    }

    private function receipt(string $type, int $id, mixed $date): string
    {
        $year = $date ? date('Y', strtotime((string) $date)) : date('Y');

        return 'NSYS-' . $type . '-' . $year . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
};
