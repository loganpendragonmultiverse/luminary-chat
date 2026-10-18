<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->is([
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'chat',
            'start-chat',
            'user-settings',
            'auth-status',
        ])) {
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        if ($request->is([
            'admin',
            'admin/*',
            'dashboard',
            'account',
            'account/*',
            'account-settings',
            'settings',
            'settings/*',
            'chats',
            'chats/*',
            'chat-folders',
            'chat-folders/*',
            'characters',
            'characters/*',
            'messages',
            'messages/*',
            'memory',
            'memory/*',
            'models',
            'models/*',
            'rooms',
            'rooms/*',
            'compare',
            'compare/*',
        ])) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
