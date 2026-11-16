<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;

class AiRunRecorder
{
    public function start(
        string $runId,
        int $userId,
        ?string $conversationId
    ): void {
        DB::table('ai_runs')->insert([
            'id' => $runId,
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'status' => 'running',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function complete(
        string $runId,
        array $summary,
        array $tools
    ): void {
        DB::transaction(function () use (
            $runId,
            $summary,
            $tools
        ) {
            DB::table('ai_runs')
                ->where('id', $runId)
                ->update([
                    'conversation_id' => $summary['conversation_id'],
                    'provider' => $summary['provider'],
                    'model' => $summary['model'],
                    'status' => 'completed',
                    'ttft_ms' => $summary['ttft_ms'],
                    'duration_ms' => $summary['duration_ms'],
                    'input_tokens' => $summary['usage']['input_tokens'],
                    'output_tokens' => $summary['usage']['output_tokens'],
                    'tool_calls' => $summary['tool_calls'],
                    'updated_at' => now(),
                ]);

            $this->saveTools($runId, $tools);
        });
    }

    public function fail(
        string $runId,
        int $durationMs,
        array $tools
    ): void {
        DB::transaction(function () use (
            $runId,
            $durationMs,
            $tools
        ) {
            DB::table('ai_runs')
                ->where('id', $runId)
                ->update([
                    'status' => 'failed',
                    'duration_ms' => $durationMs,
                    'error_code' => 'AI_STREAM_FAILED',
                    'updated_at' => now(),
                ]);

            $this->saveTools($runId, $tools);
        });
    }

    private function saveTools(
        string $runId,
        array $tools
    ): void {
        foreach ($tools as $callId => $tool) {
            DB::table('ai_tool_runs')->insert([
                'ai_run_id' => $runId,
                'tool_call_id' => $callId,
                'tool_name' => $tool['name'],
                'duration_ms' => $tool['duration_ms'],
                'successful' => $tool['success'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
