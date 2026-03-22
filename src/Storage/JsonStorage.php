<?php

declare(strict_types=1);

namespace App\Storage;

use RuntimeException;

class JsonStorage
{
    public function __construct(private string $file)
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($this->file)) {
            file_put_contents($this->file, json_encode([
                'blogs' => []
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function read(): array
    {
        $content = file_get_contents($this->file);
        if ($content === false) {
            throw new RuntimeException('Could not read storage file.');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Storage JSON is invalid.');
        }

        return $decoded;
    }

    public function write(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Failed to encode JSON for storage.');
        }

        $tmp = $this->file . '.tmp';
        file_put_contents($tmp, $json, LOCK_EX);
        rename($tmp, $this->file);
    }
}