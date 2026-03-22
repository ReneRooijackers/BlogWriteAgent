<?php

declare(strict_types=1);

namespace App\Storage;

class BlogRepository
{
    public function __construct(
        private JsonStorage $storage,
        private string $approvedDir
    ) {
        if (!is_dir($this->approvedDir)) {
            mkdir($this->approvedDir, 0777, true);
        }
    }

    public function all(): array
    {
        $data = $this->storage->read();
        return $data['blogs'] ?? [];
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $blog) {
            if (($blog['id'] ?? '') === $id) {
                return $blog;
            }
        }
        return null;
    }

    public function create(array $record): array
    {
        $data = $this->storage->read();

        $record['id'] = $record['id'] ?? $this->uuid();
        $record['created_at'] = $record['created_at'] ?? date('c');
        $record['updated_at'] = date('c');

        $data['blogs'][] = $record;
        $this->storage->write($data);

        return $record;
    }

    public function update(string $id, array $changes): ?array
    {
        $data = $this->storage->read();

        foreach ($data['blogs'] as $index => $blog) {
            if (($blog['id'] ?? '') === $id) {
                $data['blogs'][$index] = array_merge($blog, $changes, [
                    'updated_at' => date('c'),
                ]);
                $this->storage->write($data);
                return $data['blogs'][$index];
            }
        }

        return null;
    }

    public function exportApprovedMarkdown(array $blog): ?string
    {
        if (($blog['status'] ?? '') !== 'approved') {
            return null;
        }

        $slug = $this->slug($blog['title'] ?? 'blog-post');
        $filename = date('Ymd_His') . '_' . $slug . '.md';
        $path = rtrim($this->approvedDir, '/') . '/' . $filename;

        $content = "# " . ($blog['title'] ?? 'Untitled') . "\n\n";
        $content .= ($blog['excerpt'] ?? '') . "\n\n";
        $content .= ($blog['markdown'] ?? '');

        file_put_contents($path, $content);

        return $path;
    }

    private function uuid(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function slug(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? 'blog-post';
        return trim($value, '-') ?: 'blog-post';
    }
}