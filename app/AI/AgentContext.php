<?php

declare(strict_types=1);

namespace App\AI;

class AgentContext
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public array $data = []) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function set(string $key, mixed $value): self
    {
        data_set($this->data, $key, $value);

        return $this;
    }

    public function merge(array $values): self
    {
        $this->data = array_replace_recursive($this->data, $values);

        return $this;
    }

    public function has(string $key): bool
    {
        return data_get($this->data, $key) !== null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
