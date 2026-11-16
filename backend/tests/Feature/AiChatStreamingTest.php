<?php

namespace Tests\Feature;

use App\Ai\Agents\SupportAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiChatStreamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_stream_ai_chat(): void
    {
        $this->postJson('/api/ai/chat/stream', [
            'message' => 'Hello',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_receives_stream(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        SupportAgent::fake([
            'Xin chào, tôi là SupportFlow AI.',
        ]);

        $response = $this->post(
            '/api/ai/chat/stream',
            ['message' => 'Xin chào'],
            ['Accept' => 'text/event-stream']
        );

        $response->assertOk();
        $response->assertStreamed();

        // This consumes the lazy streaming response.
        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'event: run_start',
            $content
        );

        $this->assertStringContainsString(
            'event: run_done',
            $content
        );

        $this->assertDatabaseHas('ai_runs', [
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
    }

    public function test_invalid_prompt_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        SupportAgent::fake();

        $this->postJson('/api/ai/chat/stream', [
            'message' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }
}
