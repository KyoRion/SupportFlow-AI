<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\IndexKnowledgeDocument;
use App\Models\KnowledgeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = KnowledgeDocument::query()
            ->where('user_id', $request->user()->id)
            ->select([
                'id',
                'title',
                'status',
                'chunk_count',
                'created_at',
            ])
            ->latest()
            ->paginate(15);

        return response()->json($documents);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => [
                'required', 'string', 'max:180',
            ],
            'content' => [
                'required', 'string', 'min:50', 'max:40000',
            ],
        ]);

        $document = KnowledgeDocument::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => 'pending',
        ]);

        IndexKnowledgeDocument::dispatch($document->id)
            ->afterCommit();

        return response()->json([
            'id' => $document->id,
            'status' => $document->status,
        ], 201);
    }
}
