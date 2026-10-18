<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'users' => User::query()
                ->where('email', 'not like', '%@snapfinder.test')
                ->withCount(['characters', 'chatFolders', 'chats'])
                ->latest()
                ->get(),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user()) && ! $request->boolean('is_admin')) {
            return back()->withErrors([
                'is_admin' => 'You cannot remove admin access from the account you are using right now.',
            ]);
        }

        $user->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_admin' => $request->boolean('is_admin'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'User moderation settings updated.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors([
                'user' => 'You cannot delete the account you are using right now.',
            ]);
        }

        $user->delete();

        return back()->with('status', 'User deleted.');
    }
}
