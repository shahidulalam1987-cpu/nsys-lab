<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDailySubmission;
use App\Services\EmployeeSubmissionScopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DailySubmissionController extends Controller
{
    public function __construct(private EmployeeSubmissionScopeService $scopeService) {}

    public function orders(Request $request)
    {
        return $this->page($request, 'order');
    }

    public function spend(Request $request)
    {
        return $this->page($request, 'spend');
    }

    public function storeOrder(Request $request)
    {
        return $this->store($request, 'order');
    }

    public function storeSpend(Request $request)
    {
        return $this->store($request, 'spend');
    }

    private function page(Request $request, string $type)
    {
        $employee = $request->user()->employee()->with(['roleRecord', 'departmentRecord'])->firstOrFail();
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : today();
        $scope = $this->scopeService->scope($employee, $date, $type, $request->user());
        $submissions = $employee->dailySubmissions()
            ->with(['client', 'page', 'campaign'])
            ->where('submission_type', $type)
            ->latest('submission_date')
            ->latest()
            ->get();

        return view('employee.daily-submissions.index', compact('employee', 'type', 'date', 'scope', 'submissions'));
    }

    private function store(Request $request, string $type)
    {
        $employee = $request->user()->employee()->with(['roleRecord', 'departmentRecord'])->firstOrFail();
        $rules = [
            'submission_date' => ['required', 'date', 'before_or_equal:today'],
            'page_id' => ['required', 'exists:client_pages,id'],
            'campaign_id' => [$type === 'spend' ? 'required' : 'nullable', 'exists:campaigns,id'],
            'note' => ['nullable', 'string'],
        ];
        $rules += $type === 'order'
            ? [
                'orders' => ['required', 'integer', 'min:0'],
                'confirmed_orders' => ['nullable', 'integer', 'min:0'],
                'cancelled_orders' => ['nullable', 'integer', 'min:0'],
            ]
            : [
                'dollar_spend' => ['required', 'numeric', 'min:0'],
                'cpm' => ['nullable', 'numeric', 'min:0'],
                'cpc' => ['nullable', 'numeric', 'min:0'],
                'ctr' => ['nullable', 'numeric', 'min:0'],
                'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ];
        $data = $request->validate($rules);
        $date = Carbon::parse($data['submission_date'])->startOfDay();
        $selection = $this->scopeService->resolveSelection(
            $employee,
            $request->user(),
            $date,
            $type,
            (int) $data['page_id'],
            isset($data['campaign_id']) ? (int) $data['campaign_id'] : null
        );

        if ($type === 'order'
            && (int) ($data['confirmed_orders'] ?? 0) + (int) ($data['cancelled_orders'] ?? 0) > (int) $data['orders']) {
            throw ValidationException::withMessages(['orders' => 'Confirmed and cancelled orders cannot exceed total orders.']);
        }

        $key = EmployeeDailySubmission::duplicateKey(
            $employee->id,
            $date->toDateString(),
            $type,
            $selection['page_id'],
            $selection['campaign_id']
        );
        if (EmployeeDailySubmission::where('submission_key', $key)->exists()) {
            throw ValidationException::withMessages(['submission_date' => 'A submission already exists for this date, type, page, and campaign.']);
        }

        $record = array_merge($selection, $data, [
            'employee_id' => $employee->id,
            'submission_type' => $type,
            'submission_key' => $key,
            'status' => 'pending',
        ]);
        unset($record['screenshot']);
        if ($request->hasFile('screenshot')) {
            $record['screenshot_path'] = $request->file('screenshot')->store('employee-submissions/'.$employee->employee_id, 'public');
        }

        EmployeeDailySubmission::create($record);

        return redirect($type === 'order' ? '/employee/daily-orders' : '/employee/daily-spend')
            ->with('success', ucfirst($type).' submission sent for admin review.');
    }
}
