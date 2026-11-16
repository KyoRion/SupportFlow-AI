<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\SupportAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Laravel\Ai\Models\Conversation;

class AiChatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => [
                'required', 'string', 'min:1', 'max:2000',
            ],
            'conversation_id' => [
                'nullable', 'uuid',
            ],
        ]);

        $user = $request->user();
        $conversationId = $data['conversation_id'] ?? null;

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

        $response = $agent->prompt($data['message']);

        return response()->json([
            'conversation_id' => $response->conversationId,
            'reply' => $response->text,
            'usage' => [
                'input_tokens' => $response->usage->inputTokens,
                'output_tokens' => $response->usage->outputTokens,
            ],
        ]);
    }

    public function show(
        Request $request,
        string $conversationId
    ): JsonResponse {
        $user = $request->user();

        $conversation = Conversation::query()
            ->whereKey($conversationId)
            ->where('participant_type', $user->getMorphClass())
            ->where('participant_id', $user->getKey())
            ->firstOrFail();

        $messages = $conversation->messages()
            ->where('agent', SupportAgent::class)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }
}
