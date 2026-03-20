<?php

class OpenAIClient
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(string $prompt): array
    {
        $payload = [
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        if ($ch === false) {
            throw new Exception('cURL kon niet worden geïnitialiseerd.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL fout: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new Exception('Ongeldige JSON response van OpenAI: ' . $response);
        }

        if ($httpCode >= 400) {
            throw new Exception('OpenAI fout (' . $httpCode . '): ' . $response);
        }

        return $decoded;
    }
}