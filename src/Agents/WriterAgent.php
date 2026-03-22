<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\OpenAIClient;

class WriterAgent
{
    public function __construct(
        private OpenAIClient $client,
        private string $systemPrompt
    ) {
    }

    public function run(array $research, array $seo): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'excerpt' => ['type' => 'string'],
                'markdown' => ['type' => 'string'],
            ],
            'required' => ['title', 'excerpt', 'markdown'],
            'additionalProperties' => false,
        ];

        return $this->client->json([
            ['role' => 'system', 'content' => $this->systemPrompt],
            ['role' => 'user', 'content' => "Write a blog article using these inputs.\n\nResearch:\n" . json_encode($research, JSON_PRETTY_PRINT) . "\n\nSEO:\n" . json_encode($seo, JSON_PRETTY_PRINT)],
        ], $schema, 0.7);
    }
}