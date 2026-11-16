<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\SupportAgent;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiRunRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AiChatStreamController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
            'conversation_id' => ['nullable', 'uuid'],
        ]);

        $user = $request->user();
        $conversationId = $data['conversation_id'] ?? null;
        $recorder = app(AiRunRecorder::class);

        $recorder->start(
            $runId,
            (int) $user->id,
            $conversationId
        );

        // Same conversation ownership check as Checkpoint D.
        if ($conversationId !== null) {
            $store = app(ConversationStore::class);

            abort_unless(
                $store instanceof VerifiesConversationOwnership
                    && $store->conversationBelongsTo(
                        $conversationId,
                        $user->getMorphClass(),
                        $user->getKey()
                    ),
                403
            );
        }

        $agent = new SupportAgent($user);

        if ($conversationId !== null) {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        $runId = (string) Str::uuid();

        return response()->stream(function () use (
            $agent,
            $data,
            $runId,
            $user,
            $recorder
        ) {
            $startedNs = hrtime(true);

            $ttftMs = null;
            $provider = null;
            $model = null;
            $completed = null;

            $tools = [];

            try {
                yield self::frame('run_start', [
                    'run_id' => $runId,
                ]);

                $stream = $agent
                    ->stream($data['message'])
                    ->then(function (
                        StreamedAgentResponse $response
                    ) use (&$completed): void {
                        $completed = $response;
                    });

                foreach ($stream as $event) {
                    if ($event instanceof StreamStart) {
                        $provider = $event->provider;
                        $model = $event->model;
                    }

                    if ($event instanceof TextDelta) {
                        if ($ttftMs === null) {
                            $ttftMs = (int) (
                                (hrtime(true) - $startedNs) / 1_000_000
                            );
                        }

                        yield self::frame('text_delta', [
                            'delta' => $event->delta,
                        ]);
                    }

                    if ($event instanceof ToolCall) {
                        $callId = $event->toolCall->id;
                        $name = $event->toolCall->name;

                        $tools[$callId] = [
                            'name' => $name,
                            'started_ns' => hrtime(true),
                            'duration_ms' => null,
                            'success' => null,
                        ];

                        yield self::frame('tool_start', [
                            'call_id' => $callId,
                            'name' => $name,
                        ]);
                    }

                    if (
                        $event instanceof ToolResult
                        && ! $event->preliminary
                    ) {
                        $callId = $event->toolResult->id;

                        $durationMs = isset($tools[$callId])
                            ? (int) (
                                (hrtime(true) -
                                    $tools[$callId]['started_ns'])
                                / 1_000_000
                            )
                            : null;

                        if (isset($tools[$callId])) {
                            $tools[$callId]['duration_ms'] = $durationMs;
                            $tools[$callId]['success'] = $event->successful;
                        }

                        yield self::frame('tool_end', [
                            'call_id' => $callId,
                            'name' => $event->toolResult->name,
                            'success' => $event->successful,
                            'duration_ms' => $durationMs,
                        ]);
                    }
                }

                if (! $completed instanceof StreamedAgentResponse) {
                    throw new \RuntimeException(
                        'AI stream did not complete.'
                    );
                }

                $durationMs = (int) (
                    (hrtime(true) - $startedNs) / 1_000_000
                );

                $summary = [
                    'run_id' => $runId,
                    'conversation_id' => $completed->conversationId,
                    'provider' => $provider,
                    'model' => $model,
                    'ttft_ms' => $ttftMs,
                    'duration_ms' => $durationMs,
                    'tool_calls' => count($tools),
                    'usage' => [
                        'input_tokens' => $completed->usage->inputTokens,
                        'output_tokens' => $completed->usage->outputTokens,
                    ],
                ];

                Log::info('ai.run.completed', [
                    'user_id' => $user->id,
                    ...$summary,
                ]);

                $recorder->complete(
                    $runId,
                    $summary,
                    $tools
                );

                yield self::frame('run_done', $summary);
            } catch (Throwable $exception) {
                Log::error('ai.run.failed', [
                    'run_id' => $runId,
                    'user_id' => $user->id,
                    'exception' => get_class($exception),
                ]);

                report($exception);

                yield self::frame('run_error', [
                    'run_id' => $runId,
                    'code' => 'AI_STREAM_FAILED',
                    'message' => 'AI processing failed. Check server logs.',
                ]);

                try {
                    $recorder->fail(
                        $runId,
                        (int) ((hrtime(true) - $startedNs) / 1_000_000),
                        $tools
                    );
                } catch (Throwable $recordingError) {
                    report($recordingError);
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private static function frame(
        string $name,
        array $payload
    ): string {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        return "event: {$name}\ndata: {$json}\n\n";
    }
}
