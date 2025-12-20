<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Notifications\EmailTwoFactorCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EmailTwoFactorMiddleware
{
    /**
     * Handle an incoming request and enforce email two-factor authentication.
     *
     * This middleware checks if the authenticated user has email 2FA enabled
     * and redirects to the verification page if they haven't verified their
     * session yet. It also generates and sends verification codes as needed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Skip if user doesn't have email 2FA enabled
        if (! $user || ! $user->email_two_factor_enabled) {
            return $next($request);
        }

        // Skip if already verified in this session
        if (session('email_2fa_verified')) {
            return $next($request);
        }

        // Allow logout without 2FA verification
        if ($request->routeIs('filament.admin.auth.logout')) {
            return $next($request);
        }

        // Generate and send code if not already sent
        if (! session('email_2fa_code_sent')) {
            $code = $user->generateEmailTwoFactorCode();
            $user->notify(new EmailTwoFactorCode($code, 'login'));
            Log::info('Email 2FA code generated and sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            session(['email_2fa_code_sent' => true]);
        }

        // Redirect to verification page
        if (! $request->routeIs('filament.admin.auth.email-2fa')) {
            return redirect()->route('filament.admin.auth.email-2fa');
        }

        return $next($request);
    }
}
