<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/admin/login');
});

Route::get('/dashboard', function () {
    return redirect('/admin/dashboard');
})->middleware('web');