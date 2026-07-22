<?php

namespace Tests\Feature;

use App\Models\AutomationAudit;
use App\Models\AutomationTask;
use App\Models\Client;
use App\Models\SalaryPayment;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\AutomationEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_trigger_creates_task_notification_and_audit_once(): void
    {
        $admin = $this->admin();

        $task = app(AutomationEngineService::class)->trigger('client_payment_received', [
            'id' => 501,
            'title' => 'Client payment received for review',
            'due_date' => '2026-07-08',
        ], null, $admin);
        $duplicate = app(AutomationEngineService::class)->trigger('client_payment_received', [
            'id' => 501,
            'title' => 'Client payment received for review',
            'due_date' => '2026-07-08',
        ], null, $admin);

        $this->assertNotNull($task);
        $this->assertSame($task->id, $duplicate->id);
        $this->assertDatabaseCount('automation_tasks', 1);
        $this->assertDatabaseHas('automation_tasks', [
            'task_key' => 'event:client_payment_received:501',
            'department' => 'Finance',
            'priority' => 'medium',
            'due_date' => '2026-07-08 00:00:00',
        ]);
        $this->assertDatabaseHas('system_notifications', [
            'notification_key' => 'automation.task.event:client_payment_received:501',
            'type' => 'automation',
            'status' => 'unread',
        ]);
        $this->assertDatabaseHas('automation_audits', [
            'rule_key' => 'client_payment_received',
            'event_name' => 'client_payment_received',
            'result' => 'created',
        ]);
        $this->assertDatabaseHas('automation_audits', [
            'rule_key' => 'client_payment_received',
            'event_name' => 'client_payment_received',
            'result' => 'duplicate_prevented',
        ]);
    }

    public function test_automation_dashboard_detects_pending_client_payment_rule(): void
    {
        $admin = $this->admin();
        $client = Client::create([
            'company_name' => 'Automation Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);
        $payment = SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-07-01',
            'amount' => 1000,
            'payment_method' => 'Bank',
            'transaction_id' => 'AUTO-PENDING',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin/automation?department=Finance&status=pending');

        $response->assertOk();
        $response->assertSee('Automation');
        $response->assertSee('Pending Tasks');
        $response->assertSee('Client payment pending approval');
        $response->assertSee('Finance');
        $this->assertDatabaseHas('automation_tasks', [
            'task_key' => 'client_payment_pending:' . $payment->id,
            'department' => 'Finance',
            'related_module' => 'Client Payments',
        ]);
    }

    public function test_automation_task_completion_resolves_notification(): void
    {
        $admin = $this->admin();
        $task = app(AutomationEngineService::class)->trigger('payroll_approved', [
            'id' => 700,
            'title' => 'Payroll approved reminder',
        ], null, $admin);

        $this->actingAs($admin)
            ->post('/admin/automation/tasks/' . $task->id . '/complete')
            ->assertRedirect();

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertDatabaseHas('system_notifications', [
            'notification_key' => 'automation.task.event:payroll_approved:700',
            'status' => 'resolved',
        ]);
        $this->assertDatabaseHas('automation_audits', [
            'rule_key' => 'task_completion',
            'result' => 'completed',
        ]);
    }

    public function test_overdue_filter_shows_only_pending_overdue_tasks(): void
    {
        $admin = $this->admin();

        AutomationTask::create([
            'task_key' => 'overdue-task',
            'title' => 'Overdue task',
            'priority' => 'high',
            'status' => 'pending',
            'department' => 'Finance',
            'due_date' => now()->subDay()->toDateString(),
        ]);
        AutomationTask::create([
            'task_key' => 'future-task',
            'title' => 'Future task',
            'priority' => 'medium',
            'status' => 'pending',
            'department' => 'Finance',
            'due_date' => now()->addDay()->toDateString(),
        ]);
        AutomationTask::create([
            'task_key' => 'completed-overdue-task',
            'title' => 'Completed overdue task',
            'priority' => 'medium',
            'status' => 'completed',
            'department' => 'Finance',
            'due_date' => now()->subDay()->toDateString(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/automation?status=pending&overdue=1');

        $response->assertOk();
        $response->assertSee('Overdue task');
        $response->assertDontSee('Future task');
        $response->assertDontSee('Completed overdue task');
    }

    public function test_non_admin_cannot_access_automation_module(): void
    {
        $client = User::factory()->create(['role' => 'client', 'status' => 'active']);

        $this->actingAs($client)->get('/admin/automation')->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }
}
