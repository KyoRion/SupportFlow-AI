<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    DB::select('SELECT 1');

    return response()->json([
        'service' => 'supportflow-api',
        'database' => 'connected',
    ]);
});
