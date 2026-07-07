<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationTask;
use App\Services\AutomationEngineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AutomationController extends Controller
{
    public function index(Request $request, AutomationEngineService $automation)
    {
        $filters = $request->validate([
            'department' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', Rule::in(array_keys(AutomationTask::PRIORITIES))],
            'status' => ['nullable', Rule::in(array_keys(AutomationTask::STATUSES))],
            'date' => ['nullable', 'date'],
            'module' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.automation.index', $automation->dashboard($filters, $request->user()));
    }

    public function complete(Request $request, AutomationTask $task, AutomationEngineService $automation)
    {
        $automation->completeTask($task, $request->user());

        return back()->with('success', 'Automation task completed successfully.');
    }
}
