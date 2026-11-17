<?php

namespace App\Services\Knowledge;

use InvalidArgumentException;

class TextChunker
{
    public function split(
        string $text,
        int $size = 1200,
        int $overlap = 200
    ): array {
        if ($size <= 0 || $overlap < 0 || $overlap >= $size) {
            throw new InvalidArgumentException(
                'Invalid chunk configuration.'
            );
        }

        $text = trim(
            str_replace(["\r\n", "\r"], "\n", $text)
        );

        if ($text === '') {
            return [];
        }

        $length = mb_strlen($text);
        $step = $size - $overlap;
        $chunks = [];

        for ($start = 0; $start < $length; $start += $step) {
            $chunk = trim(mb_substr($text, $start, $size));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            if ($start + $size >= $length) {
                break;
            }
        }

        return $chunks;
    }
}
