<?php

namespace Tests\Feature;

use App\Models\BugReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBugTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_status_edit_and_delete_bug(): void
    {
        $admin = $this->user('admin');
        BugReport::create([
            'bug_id' => 'BUG-0099',
            'module' => 'Dashboard',
            'title' => 'Open dashboard test bug',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get('/admin/bug-tracker')
            ->assertOk()
            ->assertSee('href="/admin/bug-tracker"', false)
            ->assertSee('System Tools')
            ->assertSee('href="/admin/dashboard"', false)
            ->assertSee('Agency Dashboard')
            ->assertSee('href="/admin/marketing-operations"', false)
            ->assertSee('Marketing Operations')
            ->assertSee('href="/admin/client-dashboard"', false)
            ->assertSee('Clients')
            ->assertSee('href="/admin/employee-dashboard"', false)
            ->assertSee('Employees')
            ->assertSee('Bug Tracker')
            ->assertSee('Activity Log')
            ->assertSee('Security Audit')
            ->assertSee('Test Data Reset')
            ->assertSee('header-count-badge', false)
            ->assertSee('>1<', false)
            ->assertDontSee('Client List')
            ->assertDontSee('Employee List')
            ->assertSee('Add Bug');

        $create = $this->actingAs($admin)->post('/admin/bug-tracker', [
            'module' => 'Employee Portal',
            'title' => 'Salary slip button not visible',
            'description' => 'QA user cannot find the salary slip download link.',
            'priority' => 'high',
            'status' => 'open',
            'reported_by' => 'QA Team',
            'assigned_to' => 'Developer',
            'fixed_note' => null,
        ]);

        $bug = BugReport::firstOrFail();

        $create->assertRedirect('/admin/bug-tracker');
        $bug = BugReport::where('title', 'Salary slip button not visible')->firstOrFail();
        $this->assertSame('BUG-0100', $bug->bug_id);
        $this->assertDatabaseHas('bug_reports', [
            'bug_id' => 'BUG-0100',
            'module' => 'Employee Portal',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->post('/admin/bug-tracker/' . $bug->id . '/status', [
                'status' => 'in_progress',
            ])
            ->assertRedirect('/admin/bug-tracker');

        $this->assertDatabaseHas('bug_reports', [
            'id' => $bug->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($admin)
            ->post('/admin/bug-tracker/' . $bug->id . '/update', [
                'module' => 'Employee Portal',
                'title' => 'Salary slip button fixed',
                'description' => 'Verified link is visible.',
                'priority' => 'medium',
                'status' => 'fixed',
                'reported_by' => 'QA Team',
                'assigned_to' => 'Developer',
                'fixed_note' => 'Button label updated and verified.',
            ])
            ->assertRedirect('/admin/bug-tracker');

        $this->assertDatabaseHas('bug_reports', [
            'id' => $bug->id,
            'title' => 'Salary slip button fixed',
            'status' => 'fixed',
            'fixed_note' => 'Button label updated and verified.',
        ]);

        $this->actingAs($admin)
            ->get('/admin/bug-tracker?status=fixed')
            ->assertOk()
            ->assertSee('BUG-0100')
            ->assertSee('Salary slip button fixed');

        $this->actingAs($admin)
            ->post('/admin/bug-tracker/' . $bug->id . '/delete')
            ->assertRedirect('/admin/bug-tracker');

        $this->assertDatabaseMissing('bug_reports', ['id' => $bug->id]);
    }

    public function test_bug_tracker_is_admin_only(): void
    {
        $this->actingAs($this->user('client'))
            ->get('/admin/bug-tracker')
            ->assertForbidden();

        $this->actingAs($this->user('employee'))
            ->get('/admin/bug-tracker')
            ->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
