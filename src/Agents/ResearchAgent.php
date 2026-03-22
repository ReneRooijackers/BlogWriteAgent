<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\OpenAIClient;

class ResearchAgent
{
    public function __construct(
        private OpenAIClient $client,
        private string $systemPrompt
    ) {
    }

    public function run(string $topic): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'topic' => ['type' => 'string'],
                'audience' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'key_points' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'examples' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'risks_to_verify' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
            ],
            'required' => ['topic', 'audience', 'summary', 'key_points', 'examples', 'risks_to_verify'],
            'additionalProperties' => false,
        ];

        return $this->client->json([
            ['role' => 'system', 'content' => $this->systemPrompt],
            ['role' => 'user', 'content' => "Research this topic for a blog article: {$topic}"],
        ], $schema, 0.4);
    }
}