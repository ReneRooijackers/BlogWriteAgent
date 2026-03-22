<?php

declare(strict_types=1);

set_time_limit(300);
ini_set('max_execution_time', '300');

require dirname(__DIR__) . '/bootstrap.php';

use App\Agents\ResearchAgent;
use App\Agents\ReviewerAgent;
use App\Agents\SeoAgent;
use App\Agents\WriterAgent;
use App\Services\OpenAIClient;
use App\Storage\BlogRepository;
use App\Storage\JsonStorage;
use App\Workflow\BlogWorkflow;

$topic = $argv[1] ?? null;

if (!$topic) {
    fwrite(STDERR, "Usage: php scripts/test-openai.php \"Your topic\"\n");
    exit(1);
}

$appConfig = require base_path('config/app.php');
$agentConfig = require base_path('config/agents.php');

$client = new OpenAIClient($appConfig['openai']);
$storage = new JsonStorage($appConfig['storage']['json_db']);
$repository = new BlogRepository($storage, $appConfig['storage']['approved_dir']);

$workflow = new BlogWorkflow(
    new ResearchAgent($client, $agentConfig['research']['system']),
    new SeoAgent($client, $agentConfig['seo']['system']),
    new WriterAgent($client, $agentConfig['writer']['system']),
    new ReviewerAgent($client, $agentConfig['reviewer']['system']),
    $repository
);

try {
    $blog = $workflow->generate($topic);

    echo "Generated blog:\n";
    echo "ID: {$blog['id']}\n";
    echo "Title: {$blog['title']}\n";
    echo "Status: {$blog['status']}\n";
    echo "Excerpt: {$blog['excerpt']}\n\n";
    echo $blog['markdown'] . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error: {$e->getMessage()}\n");
    exit(1);
}