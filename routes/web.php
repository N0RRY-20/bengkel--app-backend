<?php

use App\Http\Controllers\auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth Routes
Route::get('/auth/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/callback', [SocialiteController::class, 'callback']);
