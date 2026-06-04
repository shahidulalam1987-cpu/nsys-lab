<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientUserController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'client')
            ->with('client')
            ->latest()
            ->get();

        return view('admin.client-users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.client-users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'status' => 'active',
        ]);

        return redirect('/admin/client-users')
            ->with('success', 'Client user created successfully.');
    }

    public function editPassword(User $user)
    {
        return view('admin.client-users.reset-password', compact('user'));
    }

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect('/admin/client-users')
            ->with('success', 'Password reset successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->status === 'active') {
            $user->status = 'inactive';
        } else {
            $user->status = 'active';
        }

        $user->save();

        return redirect('/admin/client-users')
            ->with('success', 'Client user status updated successfully.');
    }

    public function destroy(User $user)
    {
        $linkedClient = Client::where('user_id', $user->id)->first();

        if ($linkedClient) {
            return redirect('/admin/client-users')
                ->with('error', 'This user is linked with a client profile. Delete or unlink the client first.');
        }

        $user->delete();

        return redirect('/admin/client-users')
            ->with('success', 'Client user deleted successfully.');
    }
}