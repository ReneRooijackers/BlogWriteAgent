<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\OpenAIClient;

class ReviewerAgent
{
    public function __construct(
        private OpenAIClient $client,
        private string $systemPrompt
    ) {
    }

    public function run(array $research, array $seo, array $draft): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'approved' => ['type' => 'boolean'],
                'review_summary' => ['type' => 'string'],
                'fact_flags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'style_flags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'improved_title' => ['type' => 'string'],
                'improved_excerpt' => ['type' => 'string'],
                'improved_markdown' => ['type' => 'string'],
            ],
            'required' => [
                'approved',
                'review_summary',
                'fact_flags',
                'style_flags',
                'improved_title',
                'improved_excerpt',
                'improved_markdown'
            ],
            'additionalProperties' => false,
        ];

        return $this->client->json([
            ['role' => 'system', 'content' => $this->systemPrompt],
            [
                'role' => 'user',
                'content' => "Review and improve this draft.\n\nResearch:\n"
                    . json_encode($research, JSON_PRETTY_PRINT)
                    . "\n\nSEO:\n"
                    . json_encode($seo, JSON_PRETTY_PRINT)
                    . "\n\nDraft:\n"
                    . json_encode($draft, JSON_PRETTY_PRINT)
            ],
        ], $schema, 0.3);
    }
}