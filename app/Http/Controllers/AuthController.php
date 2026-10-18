<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('characters.index');
        }

        return view('auth.login');
    }

    public function register(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('characters.index');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $guard = Auth::guard();
        $remember = $request->boolean('remember');
        $guard->setRememberDuration((int) config('auth.remember.lifetime', 43200));

        if (! $guard->attempt($credentials, $remember)) {
            return back()->withInput()->withErrors([
                'email' => 'The provided credentials could not be verified.',
            ]);
        }

        if (! $request->user()?->is_active) {
            Auth::logout();

            return back()->withInput()->withErrors([
                'email' => 'This account is currently disabled. Contact support if you believe this is a mistake.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('characters.index');
    }

    public function signup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today()->subYears(18)->toDateString()],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['accepted'],
        ], [
            'email.unique' => 'An account already exists with this email. Log in or reset your password instead.',
        ]);

        try {
            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'date_of_birth' => $data['date_of_birth'],
                'terms_accepted_at' => now(),
                'terms_version' => '2026-07-25',
                'password' => $data['password'],
                'is_admin' => false,
                'is_active' => true,
            ]);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateEmail($exception)) {
                throw $exception;
            }

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => 'An account already exists with this email. Log in or reset your password instead.',
                ]);
        }

        return redirect()
            ->to(route('login', absolute: false))
            ->with('status', 'Account created. Log in, add a Venice or OpenRouter API key in Settings, and start chatting with Luminary.');
    }

    private function isDuplicateEmail(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'users_email_unique')
            || str_contains($message, 'users.email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(route('welcome', absolute: false));
    }
}
