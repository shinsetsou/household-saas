<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface ToolResult
{
    public function toArray(): array;

    public function toJson(): string;

    public static function success(mixed $data): self;

    public static function failure(string $message, array $context = []): self;
}
