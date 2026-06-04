<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DailyReport;
use Illuminate\Http\Request;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $query = DailyReport::with('client');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->from_date) {
            $query->whereDate('report_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('report_date', '<=', $request->to_date);
        }

        if ($request->page_name) {
            $query->where('page_name', 'like', '%' . $request->page_name . '%');
        }

        $reports = $query->latest()->get();
        $clients = Client::where('status', 'active')->get();

        return view('admin.daily-reports.index', compact('reports', 'clients'));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->get();

        return view('admin.daily-reports.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'report_date' => 'required|date',
            'page_name' => 'required|string|max:255',
            'dollar_spend' => 'required|numeric|min:0',
            'orders' => 'required|integer|min:0',
        ]);

        DailyReport::create($request->only([
            'client_id',
            'report_date',
            'page_name',
            'dollar_spend',
            'orders',
        ]));

        return redirect('/admin/daily-reports');
    }

    public function edit(DailyReport $dailyReport)
    {
        $clients = Client::where('status', 'active')->get();

        return view('admin.daily-reports.edit', compact('dailyReport', 'clients'));
    }

    public function update(Request $request, DailyReport $dailyReport)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'report_date' => 'required|date',
            'page_name' => 'required|string|max:255',
            'dollar_spend' => 'required|numeric|min:0',
            'orders' => 'required|integer|min:0',
        ]);

        $dailyReport->update($request->only([
            'client_id',
            'report_date',
            'page_name',
            'dollar_spend',
            'orders',
        ]));

        return redirect('/admin/daily-reports');
    }

    public function destroy(DailyReport $dailyReport)
    {
        $dailyReport->delete();

        return redirect('/admin/daily-reports');
    }
}