<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'shiftmate-ui')->name('home');
Route::get('/reset-password/{token}', function (\Illuminate\Http\Request $request, string $token) {
    return redirect('/?token=' . urlencode($token) . '&email=' . urlencode((string) $request->query('email')));
})->name('password.reset');
Route::redirect('/frontend', '/');
Route::redirect('/shiftmate-ui', '/');
Route::redirect('/shiftmate-ui/', '/');
