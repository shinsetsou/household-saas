<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HouseholdCsvImporter
{
    public function import(Organization $organization, UploadedFile $file): HouseholdCsvImportResult
    {
        [$rows, $errors] = $this->parse($file);

        $seen = [];
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            if (($row['name'] ?? '') === '') {
                $errors[] = "Line {$line}: name is required.";
            }
            if (! filter_var($row['block'] ?? null, FILTER_VALIDATE_INT)) {
                $errors[] = "Line {$line}: block must be an integer.";
            }
            if (! filter_var($row['lot'] ?? null, FILTER_VALIDATE_INT)) {
                $errors[] = "Line {$line}: lot must be an integer.";
            }

            $key = ($row['block'] ?? '').':'.($row['lot'] ?? '');
            if (isset($seen[$key])) {
                $errors[] = "Line {$line}: duplicate block and lot in CSV.";
            }
            $seen[$key] = true;

            if (($row['block'] ?? '') !== '' && ($row['lot'] ?? '') !== '' && Household::forOrganization($organization)
                ->where('block', $row['block'])
                ->where('lot', $row['lot'])
                ->exists()) {
                $errors[] = "Line {$line}: block and lot already exist.";
            }
        }

        if ($errors !== []) {
            return new HouseholdCsvImportResult(0, $errors);
        }

        $imported = 0;

        DB::transaction(function () use ($organization, $rows, &$imported) {
            $locked = Organization::whereKey($organization->id)->lockForUpdate()->first();

            $limit = $locked->householdLimit();
            $existing = $locked->households()->count();
            $room = $limit === null ? count($rows) : max(0, $limit - $existing);

            if ($room <= 0) {
                $message = $locked->isOnTrial()
                    ? "Your free trial allows up to {$limit} households. Upgrade to a paid plan to add more."
                    : "Your current plan allows up to {$limit} households. Upgrade to add more.";

                throw ValidationException::withMessages([
                    'csv' => $message,
                ]);
            }

            $remaining = array_slice($rows, 0, $room);

            foreach ($remaining as $row) {
                Household::create([
                    'organization_id' => $locked->id,
                    'name' => $row['name'],
                    'block' => (int) $row['block'],
                    'lot' => (int) $row['lot'],
                ]);
            }

            $imported = count($remaining);
        });

        return new HouseholdCsvImportResult($imported);
    }

    private function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [[], ['CSV file could not be read.']];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [[], ['CSV file is empty.']];
        }

        $header = array_map(fn ($column) => strtolower(trim((string) $column)), $header);
        $required = ['name', 'block', 'lot'];
        foreach ($required as $column) {
            if (! in_array($column, $header, true)) {
                fclose($handle);

                return [[], ["CSV header must include {$column}."]];
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $row = array_combine($header, array_pad($data, count($header), ''));
            $rows[] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'block' => trim((string) ($row['block'] ?? '')),
                'lot' => trim((string) ($row['lot'] ?? '')),
            ];
        }

        fclose($handle);

        return [$rows, []];
    }
}
