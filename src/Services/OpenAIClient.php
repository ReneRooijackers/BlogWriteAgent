<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class OpenAIClient
{
    private string $apiKey;
    private string $model;
    private string $endpoint;
    private int $timeout;

    public function __construct(array $config)
    {
        $this->apiKey = (string)($config['api_key'] ?? '');
        $this->model = (string)($config['model'] ?? 'gpt-4.1-mini');
        $this->endpoint = (string)($config['endpoint'] ?? 'https://api.openai.com/v1/chat/completions');
        $this->timeout = (int)($config['timeout'] ?? 120);

        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is missing.');
        }
    }

    public function json(array $messages, array $schema, float $temperature = 0.7): array
    {
        $payload = [
            'model' => $this->model,
            'temperature' => $temperature,
            'messages' => $messages,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'structured_output',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $response = $this->request($payload);

        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            throw new RuntimeException('OpenAI response content was empty.');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI JSON response could not be parsed: ' . $content);
        }

        return $decoded;
    }

    public function text(array $messages, float $temperature = 0.7): string
    {
        $payload = [
            'model' => $this->model,
            'temperature' => $temperature,
            'messages' => $messages,
        ];

        $response = $this->request($payload);

        $content = $response['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || $content === '') {
            throw new RuntimeException('OpenAI text response was empty.');
        }

        return $content;
    }

    private function request(array $payload): array
    {
        $ch = curl_init($this->endpoint);
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('OpenAI request failed: ' . $curlError);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid API response: ' . $raw);
        }

        if ($httpCode >= 400) {
            $message = $decoded['error']['message'] ?? 'Unknown API error';
            throw new RuntimeException('OpenAI API error: ' . $message);
        }

        return $decoded;
    }
}