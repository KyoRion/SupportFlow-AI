<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Knowledge\KnowledgeRetriever;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeSearchController extends Controller
{
    public function __invoke(
        Request $request,
        KnowledgeRetriever $retriever
    ): JsonResponse {
        $data = $request->validate([
            'query' => [
                'required', 'string', 'min:3', 'max:1000',
            ],
            'limit' => [
                'sometimes', 'integer', 'min:1', 'max:10',
            ],
        ]);

        $results = $retriever->search(
            $request->user(),
            $data['query'],
            $data['limit'] ?? 5
        );

        return response()->json([
            'data' => $results,
            'count' => count($results),
        ]);
    }
}
