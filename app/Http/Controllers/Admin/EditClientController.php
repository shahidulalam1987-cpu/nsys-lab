<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class EditClientController extends Controller
{
    public function edit($id)
    {
        $client = Client::findOrFail($id);

        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $request->validate([
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'client_rate' => 'required|numeric|min:0',
            'buy_rate' => 'required|numeric|min:0',
            'status' => 'required|in:active,pending,inactive',
        ]);

        $client->update($request->only([
            'company_name',
            'phone',
            'client_rate',
            'buy_rate',
            'status',
        ]));

        return redirect('/admin/clients/' . $client->id);
    }
}