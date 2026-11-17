<?php

use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AiChatStreamController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketMessageController;
use App\Http\Controllers\Api\KnowledgeDocumentController;
use App\Http\Controllers\Api\KnowledgeSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    Route::get('/me', function (Request $request) {
        return response()->json(
            $request->user()->only(['id', 'name', 'email'])
        );
    });

    Route::get(
        '/lookups/customers',
        [LookupController::class, 'customers']
    );

    Route::get(
        '/lookups/orders',
        [LookupController::class, 'orders']
    );

    Route::post('/ai/chat', [AiChatController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::post('/ai/chat/stream', AiChatStreamController::class)
        ->middleware('throttle:10,1');

    Route::get(
        '/ai/conversations/{conversationId}',
        [AiChatController::class, 'show']
    );

    Route::get('/ai/runs/{runId}', function (
        Request $request,
        string $runId
    ) {
        $run = DB::table('ai_runs')
            ->where('id', $runId)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($run, 404);

        $tools = DB::table('ai_tool_runs')
            ->where('ai_run_id', $runId)
            ->orderBy('id')
            ->get();

        return response()->json([
            'run' => $run,
            'tools' => $tools,
        ]);
    })->whereUuid('runId');

    Route::get(
        '/knowledge/documents',
        [KnowledgeDocumentController::class, 'index']
    );

    Route::post(
        '/knowledge/documents',
        [KnowledgeDocumentController::class, 'store']
    )->middleware('throttle:10,1');

    Route::get(
        '/knowledge/search',
        KnowledgeSearchController::class
    )->middleware('throttle:20,1');
});
