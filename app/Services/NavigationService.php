<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class NavigationService
{
    private ?array $navigation = null;

    public function __construct(private NavigationBadgeService $badges, private PermissionRouteRegistry $registry)
    {
    }

    public function forRequest(Request $request): array
    {
        return $this->navigation ??= $this->build($request);
    }

    public function breadcrumbs(Request $request): array
    {
        return $this->forRequest($request)['breadcrumbs'];
    }

    private function build(Request $request): array
    {
        $user = $request->user();
        $sections = collect($this->registry())
            ->map(fn (array $section) => $this->prepareSection($section, $request, $user))
            ->filter(fn (array $section) => ! empty($section['items']))
            ->values()
            ->all();

        $activeSection = collect($sections)->firstWhere('active', true) ?: ($sections[0] ?? null);
        $activeItem = $activeSection
            ? collect($activeSection['items'])->firstWhere('active', true)
            : null;

        return [
            'sections' => $sections,
            'active_section' => $activeSection,
            'active_item' => $activeItem,
            'breadcrumbs' => array_values(array_filter([
                $activeSection ? ['label' => $activeSection['label'], 'url' => $activeSection['url']] : null,
                $activeItem ? ['label' => $activeItem['label'], 'url' => $activeItem['url']] : null,
            ])),
        ];
    }

    private function prepareSection(array $section, Request $request, ?User $user): array
    {
        $items = collect($section['items'])
            ->filter(fn (array $item) => $this->canAccess($item, $user))
            ->map(fn (array $item) => $this->prepareItem($item, $request))
            ->values()
            ->all();

        $active = collect($items)->contains(fn (array $item) => $item['active']);
        $firstUrl = $items[0]['url'] ?? $section['url'];
        $badge = $this->badgeCount($section['badge'] ?? null);

        return [
            'key' => $section['key'],
            'label' => $section['label'],
            'url' => $firstUrl,
            'icon' => $section['icon'] ?? null,
            'badge_key' => $section['badge'] ?? null,
            'badge' => $badge,
            'active' => $active,
            'items' => $items,
        ];
    }

    private function prepareItem(array $item, Request $request): array
    {
        $active = match ($item['key'] ?? null) {
            'payroll_dashboard' => $request->is('admin/payroll') && ! $request->filled('status') && ! $request->filled('employee_scope'),
            'unpaid_salary' => $request->is('admin/payroll')
                && $request->query('status') === 'due'
                && $request->query('employee_scope') !== 'terminated',
            'final_settlement' => $request->is('admin/payroll')
                && $request->query('status') === 'due'
                && $request->query('employee_scope') === 'terminated',
            default => $this->matches($request, $item['active'] ?? [$item['url']]),
        };
        $badge = $this->badgeCount($item['badge'] ?? null);

        return [
            'key' => $item['key'],
            'label' => $item['label'],
            'url' => $item['url'],
            'icon' => $item['icon'] ?? null,
            'badge_key' => $item['badge'] ?? null,
            'badge' => $badge,
            'badge_danger' => $item['badge_danger'] ?? false,
            'active' => $active,
        ];
    }

    private function canAccess(array $item, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $item['permissions'] ?? [];

        if (in_array('super_admin', $permissions, true)) {
            return false;
        }

        return empty($permissions) || $user->hasAnyPermission($permissions);
    }

    private function matches(Request $request, array|string $patterns): bool
    {
        foreach ((array) $patterns as $pattern) {
            if (str_contains($pattern, '?')) {
                [$path, $query] = explode('?', $pattern, 2);
                parse_str($query, $params);
                if (! $request->is(ltrim($path, '/'))) {
                    continue;
                }
                foreach ($params as $key => $value) {
                    if ((string) $request->query($key) !== (string) $value) {
                        continue 2;
                    }
                }
                return true;
            }

            if ($request->is(ltrim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    private function badgeCount(?string $key): int
    {
        return $key ? $this->badges->count($key) : 0;
    }

    private function registry(): array
    {
        return $this->registry->navigationSections();
    }
}
