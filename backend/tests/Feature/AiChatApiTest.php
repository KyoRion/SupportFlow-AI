<?php

namespace Tests\Feature;

use App\Ai\Agents\SupportAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_ai_chat(): void
    {
        $this->postJson('/api/ai/chat', [
            'message' => 'Hello',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_chat(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        SupportAgent::fake([
            'Xin chào, tôi là SupportFlow AI.',
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Xin chào',
        ])
            ->assertOk()
            ->assertJsonPath(
                'reply',
                'Xin chào, tôi là SupportFlow AI.'
            );

        SupportAgent::assertPrompted('Xin chào');
    }

    public function test_invalid_message_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        SupportAgent::fake();

        $this->postJson('/api/ai/chat', [
            'message' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        SupportAgent::assertNeverPrompted();
    }

    public function test_user_cannot_continue_another_users_chat(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        SupportAgent::fake([
            'First response',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/ai/chat', [
            'message' => 'Start a conversation',
        ])->assertOk();

        $conversationId = $response->json('conversation_id');

        $this->assertNotNull($conversationId);

        Sanctum::actingAs($other);

        $this->postJson('/api/ai/chat', [
            'conversation_id' => $conversationId,
            'message' => 'Continue this conversation',
        ])->assertForbidden();
    }
}
