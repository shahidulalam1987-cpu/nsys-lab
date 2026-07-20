<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use App\Services\NotificationCenterService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationCenterController extends Controller
{
    public function index(Request $request, NotificationCenterService $notificationCenter)
    {
        $openNotifications = $notificationCenter->sync();

        $filters = $request->validate([
            'priority' => ['nullable', Rule::in(array_keys(SystemNotification::PRIORITIES))],
            'department' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(SystemNotification::STATUSES))],
        ]);

        $notifications = SystemNotification::with('resolver')
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['department'] ?? null, fn ($query, $department) => $query->where('department', $department))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'filters' => $filters,
            'summary' => $notificationCenter->summaryFor($openNotifications),
            'departments' => SystemNotification::query()->select('department')->distinct()->orderBy('department')->pluck('department'),
        ]);
    }

    public function updateStatus(Request $request, SystemNotification $notification)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['read', 'dismissed', 'resolved'])],
        ]);

        $notification->update([
            'status' => $data['status'],
            'resolved_at' => $data['status'] === 'resolved' ? now() : $notification->resolved_at,
            'resolved_by' => $data['status'] === 'resolved' ? auth()->id() : $notification->resolved_by,
        ]);

        return back()->with('success', 'Notification updated successfully.');
    }
}
