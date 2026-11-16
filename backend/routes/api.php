<?php

use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketMessageController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    DB::select('SELECT 1');

    return response()->json([
        'service' => 'supportflow-api',
        'database' => 'connected',
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tickets', TicketController::class)
        ->only(['index', 'show', 'store', 'update']);

    Route::post(
        'tickets/{ticket}/messages',
        [TicketMessageController::class, 'store']
    );
});
