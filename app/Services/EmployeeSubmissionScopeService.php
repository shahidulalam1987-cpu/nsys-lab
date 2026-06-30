<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EmployeeSubmissionScopeService
{
    public function __construct(private AssignmentResolver $assignmentResolver) {}

    public function canSubmitOrders(Employee $employee, User $user): bool
    {
        return $user->hasRole('moderator')
            || str_contains(mb_strtolower($employee->roleName()), 'moderator');
    }

    public function canSubmitSpend(Employee $employee, User $user): bool
    {
        return $user->hasRole('facebook_manager')
            || in_array(mb_strtolower($employee->roleName()), ['ad manager', 'facebook manager'], true);
    }

    public function scope(Employee $employee, Carbon $date, string $type, User $user): array
    {
        $this->authorizeType($employee, $user, $type);

        $widerScope = $type === 'spend' && $employee->isAgencyInternal() && $user->hasRole('facebook_manager');
        if ($widerScope) {
            $campaigns = Campaign::with(['businessManager', 'adAccount', 'client', 'page'])
                ->whereNotIn('status', ['archived'])
                ->orderBy('campaign_name')
                ->get();

            return [
                'assignments' => collect(),
                'pages' => $campaigns->pluck('page')->filter()->unique('id')->values(),
                'campaigns' => $campaigns,
                'wider_scope' => true,
            ];
        }

        $assignments = $this->assignmentResolver->allCurrent($employee, $date);
        $pageIds = $assignments->pluck('client_page_id')->filter()->unique()->values();
        $campaignIds = $assignments->pluck('campaign_id')->filter()->unique()->values();
        $pages = ClientPage::with('client')->whereIn('id', $pageIds)->orderBy('page_name')->get();
        $campaigns = Campaign::with(['businessManager', 'adAccount', 'client', 'page'])
            ->where(function ($query) use ($campaignIds, $pageIds) {
                $query->whereIn('id', $campaignIds)
                    ->orWhere(function ($query) use ($pageIds) {
                        $query->whereIn('client_page_id', $pageIds);
                    });
            })
            ->whereNotIn('status', ['archived'])
            ->orderBy('campaign_name')
            ->get();

        return compact('assignments', 'pages', 'campaigns') + ['wider_scope' => false];
    }

    public function resolveSelection(Employee $employee, User $user, Carbon $date, string $type, int $pageId, ?int $campaignId): array
    {
        $scope = $this->scope($employee, $date, $type, $user);
        $page = $scope['pages']->firstWhere('id', $pageId);
        if (! $page) {
            throw ValidationException::withMessages(['page_id' => 'You can only submit data for an assigned page.']);
        }

        $campaign = $campaignId ? $scope['campaigns']->firstWhere('id', $campaignId) : null;
        if ($campaignId && ! $campaign) {
            throw ValidationException::withMessages(['campaign_id' => 'You can only submit data for an assigned campaign.']);
        }

        if ($campaign && ((int) $campaign->client_page_id !== $pageId || (int) $campaign->client_id !== (int) $page->client_id)) {
            throw ValidationException::withMessages(['campaign_id' => 'Selected campaign does not belong to the selected page.']);
        }

        if ($type === 'spend' && ! $campaign) {
            throw ValidationException::withMessages(['campaign_id' => 'Campaign is required for a spend submission.']);
        }

        return [
            'client_id' => $page->client_id,
            'page_id' => $page->id,
            'campaign_id' => $campaign?->id,
            'bm_id' => $campaign?->business_manager_id,
            'ad_account_id' => $campaign?->ad_account_id,
        ];
    }

    private function authorizeType(Employee $employee, User $user, string $type): void
    {
        $allowed = $type === 'order'
            ? $this->canSubmitOrders($employee, $user)
            : $this->canSubmitSpend($employee, $user);

        abort_unless($allowed, 403, 'You are not authorized to submit this performance type.');
    }
}
