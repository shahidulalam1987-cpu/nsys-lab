<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemToolsStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_tools_pages_are_admin_only_and_show_sidebar_links(): void
    {
        $admin = $this->user('admin');
        $client = $this->user('client');

        $this->actingAs($client)->get('/admin/activity-log')->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin/automation')
            ->assertOk()
            ->assertSee('Automation')
            ->assertSee('Activity Log')
            ->assertSee('Security Audit')
            ->assertSee('Test Data Reset')
            ->assertSee('System Tools');

        $this->actingAs($admin)
            ->get('/admin/activity-log')
            ->assertOk()
            ->assertSee('Activity Log');

        $this->actingAs($admin)
            ->get('/admin/security-audit')
            ->assertOk()
            ->assertSee('Admin routes protected')
            ->assertSee('Pending risky GET actions');

        $this->actingAs($admin)
            ->get('/admin/test-data-reset')
            ->assertOk()
            ->assertSee('RESET TEST DATA')
            ->assertSee('Environment:')
            ->assertSee('High Risk');
    }

    public function test_bug_tracker_actions_write_activity_log_and_module_filter_works(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->post('/admin/bug-tracker', [
                'module' => 'Payroll',
                'title' => 'Generated salary needs review',
                'description' => 'Testing payroll issue.',
                'priority' => 'high',
                'status' => 'open',
                'reported_by' => 'QA',
                'assigned_to' => 'Admin',
                'fixed_note' => null,
            ])
            ->assertRedirect('/admin/bug-tracker');

        $bug = BugReport::where('module', 'Payroll')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'module' => 'Bug Tracker',
            'action' => 'Bug Created',
        ]);

        $this->actingAs($admin)
            ->get('/admin/bug-tracker?module=Payroll')
            ->assertOk()
            ->assertSee('Generated salary needs review')
            ->assertSee('High');

        $this->actingAs($admin)
            ->post('/admin/bug-tracker/' . $bug->id . '/status', ['status' => 'closed'])
            ->assertRedirect('/admin/bug-tracker');

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Bug Tracker',
            'action' => 'Bug Closed',
        ]);
    }

    public function test_test_data_reset_requires_confirmation_and_can_clear_bug_tracker_data(): void
    {
        $admin = $this->user('admin');

        BugReport::create([
            'bug_id' => 'BUG-0001',
            'module' => 'QA',
            'title' => 'Temporary test bug',
            'priority' => 'low',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->post('/admin/test-data-reset', [
                'confirmation' => 'WRONG',
                'options' => ['bug_tracker_test_data'],
            ])
            ->assertSessionHasErrors('confirmation');

        $this->actingAs($admin)
            ->post('/admin/test-data-reset', [
                'confirmation' => 'RESET TEST DATA',
                'options' => ['bug_tracker_test_data'],
            ])
            ->assertSessionHasErrors('acknowledge_high_risk');

        $this->actingAs($admin)
            ->post('/admin/test-data-reset', [
                'confirmation' => 'RESET TEST DATA',
                'options' => ['bug_tracker_test_data'],
                'acknowledge_high_risk' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('bug_reports', 0);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'System Tools',
            'action' => 'Test Data Reset',
        ]);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
