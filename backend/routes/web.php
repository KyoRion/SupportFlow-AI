<?php

use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [SessionController::class, 'store'])
    ->middleware('throttle:5,1');

Route::post('/auth/logout', [SessionController::class, 'destroy'])
    ->middleware('auth');
