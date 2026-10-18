<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ForgeMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function forgot(): View { return view('auth.forgot-password', ['captchaQuestion' => $this->refreshCaptcha('forgot_captcha')]); }
    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'captcha_answer' => ['required', 'string']]);
        if (! $this->validateCaptcha($data['captcha_answer'], 'forgot_captcha')) return back()->withInput()->withErrors(['captcha_answer' => 'The bot check answer was incorrect.']);
        $user = User::where('email', $data['email'])->first();
        if ($user) {
            $token = Password::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
            ForgeMailer::sendRaw($user->email, $user->name, 'Reset your Luminary password', "Reset your Luminary password\n\n{$url}\n\nIf you did not request this, ignore this email.", 'Luminary Chat');
        }
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
    public function reset(Request $request, string $token): View { return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email'), 'captchaQuestion' => $this->refreshCaptcha('reset_captcha')]); }
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', 'min:12'], 'captcha_answer' => ['required', 'string']]);
        if (! $this->validateCaptcha($data['captcha_answer'], 'reset_captcha')) return back()->withInput()->withErrors(['captcha_answer' => 'The bot check answer was incorrect.']);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, string $password): void { $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save(); });
        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', __($status)) : back()->withInput()->withErrors(['email' => __($status)]);
    }
}
