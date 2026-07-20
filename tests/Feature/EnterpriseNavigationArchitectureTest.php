<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseNavigationArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_level_labels_match_enterprise_navigation_structure(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/dashboard');

        $response->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee('Agency Operations')
            ->assertSee('Clients')
            ->assertSee('Employees')
            ->assertSee('Business Management')
            ->assertSee('Finance')
            ->assertSee('System Tools')
            ->assertDontSee('Page Management')
            ->assertDontSee('Marketing Operations')
            ->assertDontSee('Facebook Dashboard')
            ->assertDontSee('TikTok');
    }

    public function test_agency_operations_uses_existing_marketing_routes_and_hides_legacy_labels(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/marketing-operations');

        $response->assertOk()
            ->assertSee('Agency Operations')
            ->assertSee('/admin/marketing-operations/moderator/operations', false)
            ->assertSee('/admin/employee-submissions', false)
            ->assertSee('/admin/daily-reports', false)
            ->assertDontSee('Legacy Meta Tools')
            ->assertDontSee('Facebook Dashboard');
    }

    public function test_business_management_contains_pages_campaigns_and_reporting(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/business-managers')
            ->assertOk()
            ->assertSee('Business Management')
            ->assertSee('Business Managers')
            ->assertSee('Pages')
            ->assertSee('Ad Accounts')
            ->assertSee('Ad Account Ledger')
            ->assertSee('Campaigns')
            ->assertSee('Daily Performance')
            ->assertDontSee('Card Management');

        $this->actingAs($admin)->get('/admin/client-pages')
            ->assertOk()
            ->assertSee('Business Management')
            ->assertSee('Pages')
            ->assertSee('Campaigns')
            ->assertSee('Daily Performance')
            ->assertDontSee('Employee Submissions');
    }

    public function test_card_management_and_automation_are_not_duplicate_primary_links(): void
    {
        $admin = $this->admin();

        $finance = $this->actingAs($admin)->get('/admin/financial-management')->assertOk();
        $finance->assertSee('Card Management');
        $finance->assertDontSee('Automation');

        $system = $this->actingAs($admin)->get('/admin/automation')->assertOk();
        $system->assertSee('Automation');
        $system->assertDontSee('Card Management');
    }

    public function test_active_navigation_and_breadcrumbs_resolve_for_key_routes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/payroll?status=due')
            ->assertOk()
            ->assertSee('Employees')
            ->assertSee('Unpaid Salary');

        $this->actingAs($admin)->get('/admin/business-managers')
            ->assertOk()
            ->assertSee('Business Management')
            ->assertSee('Business Managers');

        $this->actingAs($admin)->get('/admin/marketing-operations/auditor/operations')
            ->assertOk()
            ->assertSee('Agency Operations')
            ->assertSee('Auditor Operations');
    }

    public function test_menu_items_are_hidden_when_permission_is_missing(): void
    {
        $user = $this->adminWithPermissions(['clients.view']);

        $this->actingAs($user)->get('/admin/client-dashboard')
            ->assertOk()
            ->assertSee('Clients')
            ->assertDontSee('Finance')
            ->assertDontSee('Business Management')
            ->assertDontSee('Page Management')
            ->assertDontSee('System Tools');
    }

    public function test_navigation_registry_is_shared_for_desktop_and_mobile_rendering(): void
    {
        $admin = $this->admin();
        $request = request()->create('/admin/client-pages', 'GET');
        $request->setUserResolver(fn () => $admin);

        $navigation = app(NavigationService::class)->forRequest($request);
        $labels = collect($navigation['sections'])->pluck('label')->all();

        $this->assertContains('Admin Dashboard', $labels);
        $this->assertContains('Agency Operations', $labels);
        $this->assertContains('Business Management', $labels);
        $this->assertNotContains('Page Management', $labels);
        $this->assertSame($navigation['sections'], app(NavigationService::class)->forRequest($request)['sections']);
    }

    public function test_admin_layout_does_not_contain_direct_model_queries(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringNotContainsString('::where', $layout);
        $this->assertStringNotContainsString('::all', $layout);
        $this->assertStringNotContainsString('NotificationCenterService::class)->summary()', $layout);
        $this->assertStringNotContainsString('Legacy Meta Tools', $layout);
    }

    public function test_rendering_dashboard_does_not_mutate_notifications(): void
    {
        $notification = SystemNotification::create([
            'notification_key' => 'test.open',
            'department' => 'System',
            'priority' => 'information',
            'message' => 'Existing alert',
            'action_url' => '/admin/dashboard',
            'target_team' => 'Admin',
            'type' => 'alert',
            'status' => 'unread',
        ]);

        $beforeCount = SystemNotification::count();
        $beforeUpdatedAt = $notification->updated_at;

        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk();

        $notification->refresh();
        $this->assertSame($beforeCount, SystemNotification::count());
        $this->assertSame('unread', $notification->status);
        $this->assertTrue($beforeUpdatedAt->equalTo($notification->updated_at));
    }

    public function test_visible_navigation_links_use_existing_routes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/business-managers')->assertOk();
        $this->actingAs($admin)->get('/admin/ad-accounts')->assertOk();
        $this->actingAs($admin)->get('/admin/client-pages')->assertOk();
        $this->actingAs($admin)->get('/admin/campaigns')->assertOk();
        $this->actingAs($admin)->get('/admin/marketing-operations/moderator/operations')->assertOk();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function adminWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'Limited Admin', 'slug' => 'limited_admin']);
        foreach ($permissions as $key) {
            $permission = Permission::firstOrCreate(
                ['key' => $key],
                ['name' => ucwords(str_replace(['.', '_'], ' ', $key)), 'module' => str($key)->before('.')->toString()]
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user->roles()->sync([$role->id]);

        return $user;
    }
}
