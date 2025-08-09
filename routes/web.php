<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Controllers\PoCustomerController;
use App\Http\Controllers\PoSupplierController;
use App\Http\Controllers\SuratJalanController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


Route::get('/', function () {
    return redirect('/admin'); // atau sesuai prefix panel Filament Anda
})->name('home');

// Email Verification Routes
Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    $user = User::findOrFail($request->route('id'));

    // Verifikasi hash
    if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
        abort(403, 'Invalid verification link.');
    }

    // Cek apakah URL sudah expired
    if (! $request->hasValidSignature()) {
        return redirect()->route('home')->with('error', 'Link verifikasi sudah kedaluwarsa. Silakan minta link verifikasi baru.');
    }

    // Jika email belum diverifikasi, verifikasi sekarang
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }
    // Login user secara otomatis
    Auth::login($user);

    return redirect()->route('home')->with('status', 'Email berhasil diverifikasi dan Anda telah login ke sistem!');
})->middleware(['signed'])->name('verification.verify');

// Route untuk resend verification (opsional, jika ada form untuk resend)
Route::post('/email/resend-verification', function (Request $request) {
    $request->validate(['email' => 'required|email|exists:users,email']);

    $user = User::where('email', $request->email)->first();

    if ($user->hasVerifiedEmail()) {
        return back()->with('error', 'Email sudah diverifikasi. Silakan login.');
    }

    $user->sendEmailVerificationNotification();

    return back()->with('status', 'Link verifikasi baru telah dikirim ke email Anda.');
})->name('verification.resend');

// Password Reset Routes (tanpa middleware guest)
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink($request->only('email'));

    return $status === Password::RESET_LINK_SENT
                ? back()->with(['status' => __($status)])
                : back()->withErrors(['email' => __($status)]);
})->name('password.email');

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => $request->query('email')
    ]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Str::random(60));

            $user->save();
            event(new PasswordReset($user));
        }
    );

    return $status === Password::PASSWORD_RESET
                ? redirect()->route('home')->with('status', __($status))
                : back()->withErrors(['email' => [__($status)]]);
})->name('password.update');

// Route::get('/po-customer/{poCustomer}/print', [PoCustomerController::class, 'print'])
//     ->name('po-customer.print')
//     ->middleware('auth');

// Route::get('/po-supplier/{poSupplier}/print', [PoSupplierController::class, 'print'])
//     ->name('po-supplier.print')
//     ->middleware('auth');

//     Route::middleware('auth')->group(function () {
//     Route::get('/surat-jalan/{suratJalan}/pdf', [SuratJalanController::class, 'generatePdf'])
//         ->name('surat-jalan.pdf');
// });
