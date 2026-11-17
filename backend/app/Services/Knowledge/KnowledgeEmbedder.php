<?php

namespace App\Services\Knowledge;

use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

class KnowledgeEmbedder
{
    public const MODEL = 'text-embedding-3-small';
    public const DIMENSIONS = 1536;

    public function embedMany(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = Embeddings::for($texts)
            ->dimensions(self::DIMENSIONS)
            ->generate(Lab::OpenAI, self::MODEL);

        $vectors = $response->embeddings;

        if (count($vectors) !== count($texts)) {
            throw new RuntimeException(
                'Embedding output count mismatch.'
            );
        }

        foreach ($vectors as $vector) {
            if (count($vector) !== self::DIMENSIONS) {
                throw new RuntimeException(
                    'Embedding dimension mismatch.'
                );
            }
        }

        return $vectors;
    }

    public function embedQuery(string $query): array
    {
        return $this->embedMany([$query])[0];
    }
}
