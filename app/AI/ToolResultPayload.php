<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\ToolResult;

final class ToolResultPayload implements ToolResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly mixed $data,
        public readonly ?string $error,
        public readonly array $context,
    ) {}

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'data' => $this->data,
            'error' => $this->error,
            'context' => $this->context,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function success(mixed $data): self
    {
        return new self(true, $data, null, []);
    }

    public static function failure(string $message, array $context = []): self
    {
        return new self(false, null, $message, $context);
    }
}
