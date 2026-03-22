<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\OpenAIClient;

class SeoAgent
{
    public function __construct(
        private OpenAIClient $client,
        private string $systemPrompt
    ) {
    }

    public function run(array $research): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'search_intent' => ['type' => 'string'],
                'primary_keyword' => ['type' => 'string'],
                'secondary_keywords' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'title_options' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'meta_description' => ['type' => 'string'],
                'outline' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'heading' => ['type' => 'string'],
                            'subpoints' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ],
                        ],
                        'required' => ['heading', 'subpoints'],
                        'additionalProperties' => false,
                    ]
                ],
            ],
            'required' => [
                'search_intent',
                'primary_keyword',
                'secondary_keywords',
                'title_options',
                'meta_description',
                'outline'
            ],
            'additionalProperties' => false,
        ];

        return $this->client->json([
            ['role' => 'system', 'content' => $this->systemPrompt],
            ['role' => 'user', 'content' => "Create an SEO brief from this research:\n" . json_encode($research, JSON_PRETTY_PRINT)],
        ], $schema, 0.5);
    }
}