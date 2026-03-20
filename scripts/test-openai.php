<?php

require __DIR__ . '/../agents/OpenAIClient.php';

$config = require __DIR__ . '/../config/config.php';

$apiKey = $config['openai_api_key'] ?? '';

if (!$apiKey) {
    die("Geen OPENAI_API_KEY gevonden in .env\n");
}

$client = new OpenAIClient($apiKey);

try {
    $response = $client->chat('Geef 3 blogonderwerpen over tiny houses.');
    echo "<pre>";
    print_r($response);
    echo "</pre>";
} catch (Exception $e) {
    echo 'Fout: ' . $e->getMessage();
}