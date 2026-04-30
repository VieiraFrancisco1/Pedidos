<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class JsonRequest
{
    private array $data;

    public function __construct()
    {
        $this->data = $this->parseInput();
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    private function parseInput(): array
    {
        $content = file_get_contents('php://input');

        if (!$content) {
            return [];
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Corpo JSON inválido.');
        }

        return $data;
    }
}
