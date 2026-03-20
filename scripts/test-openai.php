<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../agents/OpenAIClient.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = require __DIR__ . '/../config/config.php';

$apiKey = $config['openai_api_key'] ?? '';

if (!$apiKey) {
    die("Geen OPENAI_API_KEY gevonden in .env\n");
}

$client = new OpenAIClient($apiKey);

try {
    $response = $client->chat('Geef 3 blogonderwerpen over tiny houses.');

    $text = $response['choices'][0]['message']['content'] ?? null;

    if (!$text) {
        echo "Geen tekst ontvangen van OpenAI.\n";
        print_r($response);
        exit;
    }

    echo "\n=== ONDERWERPEN ===\n";
    echo $text . "\n\n";

    $choice = readline("Welk onderwerp wil je uitwerken? Typ 1, 2 of 3: ");

    $prompts = [
        '1' => 'Schrijf een SEO-blog van ongeveer 1000 woorden over: De voordelen van wonen in een tiny house: duurzaam, betaalbaar en minimalistisch.',
        '2' => 'Schrijf een SEO-blog van ongeveer 1000 woorden over: Tips voor het ontwerpen van een functioneel tiny house met beperkte ruimte.',
        '3' => 'Schrijf een SEO-blog van ongeveer 1000 woorden over: Leven in een tiny house: ervaringen en uitdagingen van tiny house bewoners.',
    ];

    if (!isset($prompts[$choice])) {
        exit("Ongeldige keuze. Stoppen.\n");
    }

    echo "\nBlog wordt gegenereerd...\n\n";

    $blogResponse = $client->chat($prompts[$choice]);
    $blogText = $blogResponse['choices'][0]['message']['content'] ?? null;

    if (!$blogText) {
        echo "Geen blogtekst ontvangen van OpenAI.\n";
        print_r($blogResponse);
        exit;
    }

    echo "=== BLOGPOST ===\n";
    echo $blogText . "\n";

} catch (Exception $e) {
    echo 'Fout: ' . $e->getMessage() . PHP_EOL;
}