<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
})->name('home');

Route::redirect('/dashboard', '/admin')->name('dashboard');

// Email 2FA Challenge Route
Route::get('/admin/email-2fa', \App\Filament\Pages\Auth\EmailTwoFactorChallenge::class)
    ->middleware(['web', 'auth'])
    ->name('filament.admin.auth.email-2fa');

// Language switching route
Route::post('/language', function (Request $request) {
    $language = $request->input('language');
    $supportedLocales = ['en', 'de'];

    if (in_array($language, $supportedLocales)) {
        // Store in session
        $request->session()->put('locale', $language);

        // Update user preference if authenticated
        if (Auth::check()) {
            /** @var \App\Models\User $user * */
            $user = Auth::user();
            $user->update(['language' => $language]);
        }
    }

    return back();
})->middleware(['web'])->name('language.switch');
