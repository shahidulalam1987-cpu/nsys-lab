<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientPageController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientPage::with('client')
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status));

        return view('admin.client-pages.index', [
            'pages' => $query->latest()->get(),
            'clients' => Client::orderBy('company_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.client-pages.create', [
            'page' => null,
            'clients' => Client::orderBy('company_name')->get(),
            'platforms' => ClientPage::PLATFORMS,
        ]);
    }

    public function store(Request $request)
    {
        ClientPage::create($this->validatedData($request));

        return redirect('/admin/client-pages')->with('success', 'Client page saved successfully.');
    }

    public function edit(ClientPage $page)
    {
        return view('admin.client-pages.edit', [
            'page' => $page,
            'clients' => Client::orderBy('company_name')->get(),
            'platforms' => ClientPage::PLATFORMS,
        ]);
    }

    public function update(Request $request, ClientPage $page)
    {
        $page->update($this->validatedData($request));

        return redirect('/admin/client-pages')->with('success', 'Client page updated successfully.');
    }

    public function destroy(ClientPage $page)
    {
        $page->delete();

        return redirect('/admin/client-pages')->with('success', 'Client page deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'page_name' => ['required', 'string', 'max:255'],
            'page_url' => ['nullable', 'url', 'max:1000'],
            'platform' => ['required', Rule::in(ClientPage::PLATFORMS)],
            'status' => ['required', 'in:active,inactive'],
            'note' => ['nullable', 'string'],
        ]);
    }
}
