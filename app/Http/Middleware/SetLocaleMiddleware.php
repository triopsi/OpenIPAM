<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request and set the application locale.
     *
     * This middleware determines the appropriate locale by checking:
     * 1. Authenticated user's language preference
     * 2. Session locale override
     * 3. Request language parameter
     * 4. Default application locale as fallback
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has a language preference
        if (Auth::check() && Auth::user()->language) {
            $locale = Auth::user()->language;
        }
        // Check session for language override
        elseif ($request->session()->has('locale')) {
            $locale = $request->session()->get('locale');
        }
        // Check if language is provided in request (for language switching)
        elseif ($request->has('language')) {
            $locale = $request->get('language');
            // Store in session for unauthenticated users
            $request->session()->put('locale', $locale);
        }
        // Fall back to default application locale
        else {
            $locale = config('app.locale');
        }

        // Validate locale and set application locale
        $supportedLocales = array_keys(config('languages.supported'));
        if (in_array($locale, $supportedLocales)) {
            // App::setLocale($locale);
            app()->setLocale($locale);
            Log::info('Locale set to: '.$locale.' (Selected language: '.$locale.')');
        }

        return $next($request);
    }
}
