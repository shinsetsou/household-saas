<?php

namespace App\Services;

class HouseholdCsvImportResult
{
    public function __construct(
        public readonly int $imported,
        public readonly array $errors = [],
    ) {}

    public function successful(): bool
    {
        return $this->errors === [];
    }
}
