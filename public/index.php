<?php

declare(strict_types=1);

session_start();
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

$appConfig = require base_path('config/app.php');
$agentConfig = require base_path('config/agents.php');

$storage = new JsonStorage($appConfig['storage']['json_db']);
$repository = new BlogRepository($storage, $appConfig['storage']['approved_dir']);

$error = null;
$message = null;
$currentBlog = null;

try {
    $client = new OpenAIClient($appConfig['openai']);
    $workflow = new BlogWorkflow(
        new ResearchAgent($client, $agentConfig['research']['system']),
        new SeoAgent($client, $agentConfig['seo']['system']),
        new WriterAgent($client, $agentConfig['writer']['system']),
        new ReviewerAgent($client, $agentConfig['reviewer']['system']),
        $repository
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'generate') {
            $topic = trim((string)($_POST['topic'] ?? ''));
            if ($topic === '') {
                throw new RuntimeException('Topic is required.');
            }

            $currentBlog = $workflow->generate($topic);
            $_SESSION['current_blog_id'] = $currentBlog['id'];
            $message = 'Blog draft generated.';
        }

        if ($action === 'approve') {
            $id = (string)($_POST['id'] ?? '');
            $currentBlog = $workflow->approve($id);
            $_SESSION['current_blog_id'] = $currentBlog['id'] ?? null;
            $message = 'Blog approved and exported.';
        }

        if ($action === 'reject') {
            $id = (string)($_POST['id'] ?? '');
            $reason = trim((string)($_POST['reason'] ?? 'Rejected by human reviewer.'));
            $currentBlog = $workflow->reject($id, $reason);
            $_SESSION['current_blog_id'] = $currentBlog['id'] ?? null;
            $message = 'Blog rejected.';
        }

        if ($action === 'revise') {
            $id = (string)($_POST['id'] ?? '');
            $feedback = trim((string)($_POST['feedback'] ?? ''));
            if ($feedback === '') {
                throw new RuntimeException('Revision feedback is required.');
            }
            $currentBlog = $workflow->revise($id, $feedback);
            $_SESSION['current_blog_id'] = $currentBlog['id'] ?? null;
            $message = 'Blog revised.';
        }
    }

    if ($currentBlog === null && !empty($_SESSION['current_blog_id'])) {
        $currentBlog = $repository->find((string)$_SESSION['current_blog_id']);
    }

    $allBlogs = array_reverse($repository->all());
} catch (Throwable $e) {
    $error = $e->getMessage();
    $allBlogs = array_reverse($repository->all());
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Blog AI Workflow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f6f7fb; color: #222; }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 20px; }
        textarea, input[type="text"] { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #ccc; border-radius: 8px; }
        button { padding: 10px 14px; border: 0; border-radius: 8px; cursor: pointer; }
        .btn-primary { background: #0d6efd; color: white; }
        .btn-success { background: #198754; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #fd7e14; color: white; }
        .muted { color: #666; font-size: 14px; }
        pre { white-space: pre-wrap; word-break: break-word; background: #fafafa; padding: 12px; border-radius: 8px; border: 1px solid #eee; }
        .status { font-weight: bold; }
        ul { padding-left: 20px; }
        .full { grid-column: 1 / -1; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <h1>Blog AI Workflow</h1>
    <p class="muted">Research agent → SEO agent → Writer agent → Reviewer agent → Human approval</p>

    <?php if ($message): ?>
        <div class="card"><strong><?= h($message) ?></strong></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="card"><strong>Error:</strong> <?= h($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Generate a blog</h2>
        <form method="post">
            <input type="hidden" name="action" value="generate">
            <label for="topic">Topic</label><br><br>
            <input type="text" id="topic" name="topic" placeholder="e.g. How AI agents can improve content operations" required>
            <br><br>
            <button class="btn-primary" type="submit">Generate</button>
        </form>
    </div>

    <div class="grid">
        <div>
            <?php if ($currentBlog): ?>
                <div class="card">
                    <h2>Current draft</h2>
                    <p><strong>Title:</strong> <?= h((string)$currentBlog['title']) ?></p>
                    <p><strong>Status:</strong> <span class="status"><?= h((string)$currentBlog['status']) ?></span></p>
                    <p><strong>Excerpt:</strong> <?= h((string)$currentBlog['excerpt']) ?></p>
                </div>

                <div class="card">
                    <h3>Reviewer summary</h3>
                    <p><?= h((string)($currentBlog['review']['review_summary'] ?? '')) ?></p>

                    <h4>Fact flags</h4>
                    <ul>
                        <?php foreach (($currentBlog['review']['fact_flags'] ?? []) as $flag): ?>
                            <li><?= h((string)$flag) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <h4>Style flags</h4>
                    <ul>
                        <?php foreach (($currentBlog['review']['style_flags'] ?? []) as $flag): ?>
                            <li><?= h((string)$flag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="card">
                    <h3>Human review actions</h3>

                    <form method="post" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= h((string)$currentBlog['id']) ?>">
                        <button class="btn-success" type="submit">Approve</button>
                    </form>

                    <form method="post" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?= h((string)$currentBlog['id']) ?>">
                        <label>Reject reason</label><br><br>
                        <textarea name="reason" rows="3" placeholder="Why is this rejected?"></textarea><br><br>
                        <button class="btn-danger" type="submit">Reject</button>
                    </form>

                    <form method="post">
                        <input type="hidden" name="action" value="revise">
                        <input type="hidden" name="id" value="<?= h((string)$currentBlog['id']) ?>">
                        <label>Revision feedback</label><br><br>
                        <textarea name="feedback" rows="5" placeholder="E.g. make it more technical, shorten the intro, add examples for SaaS teams"></textarea><br><br>
                        <button class="btn-warning" type="submit">Request revision</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($currentBlog): ?>
                <div class="card">
                    <h2>Markdown draft</h2>
                    <pre><?= h((string)$currentBlog['markdown']) ?></pre>
                </div>
            <?php endif; ?>
        </div>

        <div class="card full">
            <h2>Stored blogs</h2>
            <?php if (empty($allBlogs)): ?>
                <p class="muted">No blogs stored yet.</p>
            <?php else: ?>
                <?php foreach ($allBlogs as $blog): ?>
                    <div style="padding:12px 0; border-bottom:1px solid #eee;">
                        <strong><?= h((string)$blog['title']) ?></strong><br>
                        <span class="muted"><?= h((string)$blog['topic']) ?> · <?= h((string)$blog['status']) ?> · <?= h((string)$blog['updated_at']) ?></span>
                        <?php if (!empty($blog['export_path'])): ?>
                            <br><span class="muted">Exported: <?= h((string)$blog['export_path']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>