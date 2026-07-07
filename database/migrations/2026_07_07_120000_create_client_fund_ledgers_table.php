<?php

use App\Models\DailyReport;
use App\Models\EmployeePayroll;
use App\Models\Payment;
use App\Models\SalaryPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_fund_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('fund_type');
            $table->string('direction');
            $table->decimal('amount_bdt', 16, 2);
            $table->decimal('balance_before', 16, 2);
            $table->decimal('balance_after', 16, 2);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'fund_type']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::table('salary_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_payments', 'fund_type')) {
                $table->string('fund_type')->default('employee_salary')->after('client_id');
            }
        });

        DB::table('salary_payments')
            ->whereNull('fund_type')
            ->orWhere('fund_type', '')
            ->update(['fund_type' => 'employee_salary']);

        $this->backfillApprovedSalaryPayments();
        $this->backfillApprovedAdPayments();
        $this->backfillLegacyDailyReports();
        $this->backfillPaidPayrolls();
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            if (Schema::hasColumn('salary_payments', 'fund_type')) {
                $table->dropColumn('fund_type');
            }
        });

        Schema::dropIfExists('client_fund_ledgers');
    }

    private function backfillApprovedSalaryPayments(): void
    {
        DB::table('salary_payments')
            ->where('status', 'approved')
            ->orderBy('id')
            ->get()
            ->each(function ($payment) {
                $this->appendLedger(
                    (int) $payment->client_id,
                    'employee_salary',
                    'credit',
                    (float) $payment->amount,
                    SalaryPayment::class,
                    (int) $payment->id,
                    $payment->transaction_id ?: 'legacy-salary-payment:' . $payment->id,
                    'Legacy Imported: Client salary fund deposit.'
                );
            });
    }

    private function backfillApprovedAdPayments(): void
    {
        DB::table('payments')
            ->where('status', 'approved')
            ->orderBy('id')
            ->get()
            ->each(function ($payment) {
                $this->appendLedger(
                    (int) $payment->client_id,
                    'facebook_ads',
                    'credit',
                    (float) $payment->amount,
                    Payment::class,
                    (int) $payment->id,
                    $payment->transaction_id ?: 'legacy-payment:' . $payment->id,
                    'Legacy Imported: Client Facebook ads fund deposit.'
                );
            });
    }

    private function backfillLegacyDailyReports(): void
    {
        DB::table('daily_reports')
            ->join('clients', 'clients.id', '=', 'daily_reports.client_id')
            ->select('daily_reports.*', 'clients.client_rate')
            ->orderBy('daily_reports.id')
            ->get()
            ->each(function ($report) {
                $amount = round((float) $report->dollar_spend * (float) $report->client_rate, 2);
                if ($amount <= 0) {
                    return;
                }

                $this->appendLedger(
                    (int) $report->client_id,
                    'facebook_ads',
                    'debit',
                    $amount,
                    DailyReport::class,
                    (int) $report->id,
                    'legacy-daily-report:' . $report->id,
                    'Legacy Imported: Facebook ads spend for ' . $report->report_date . '.',
                    true
                );
            });
    }

    private function backfillPaidPayrolls(): void
    {
        DB::table('employee_payrolls')
            ->whereNotNull('client_id')
            ->where('paid_amount', '>', 0)
            ->orderBy('id')
            ->get()
            ->each(function ($payroll) {
                $this->appendLedger(
                    (int) $payroll->client_id,
                    'employee_salary',
                    'debit',
                    (float) $payroll->paid_amount,
                    EmployeePayroll::class,
                    (int) $payroll->id,
                    $payroll->transaction_id ?: 'legacy-payroll:' . $payroll->id,
                    'Legacy Imported: Employee salary paid.',
                    true
                );
            });
    }

    private function appendLedger(
        int $clientId,
        string $fundType,
        string $direction,
        float $amount,
        string $sourceType,
        int $sourceId,
        ?string $reference,
        string $description,
        bool $allowNegative = false
    ): void {
        if ($amount <= 0) {
            return;
        }

        if (DB::table('client_fund_ledgers')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('fund_type', $fundType)
            ->where('direction', $direction)
            ->exists()) {
            return;
        }

        $balanceBefore = (float) DB::table('client_fund_ledgers')
            ->where('client_id', $clientId)
            ->where('fund_type', $fundType)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance');
        $balanceAfter = $direction === 'credit'
            ? round($balanceBefore + $amount, 2)
            : round($balanceBefore - $amount, 2);

        if (! $allowNegative && $balanceAfter < 0) {
            return;
        }

        DB::table('client_fund_ledgers')->insert([
            'client_id' => $clientId,
            'fund_type' => $fundType,
            'direction' => $direction,
            'amount_bdt' => round($amount, 2),
            'balance_before' => round($balanceBefore, 2),
            'balance_after' => $balanceAfter,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference' => $reference,
            'description' => $description,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
