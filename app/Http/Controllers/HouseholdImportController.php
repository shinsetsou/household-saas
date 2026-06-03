<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Services\HouseholdCsvImporter;
use Illuminate\Http\Request;

class HouseholdImportController extends Controller
{
    public function create(Organization $organization)
    {
        return view('households.import', compact('organization'));
    }

    public function sample(Organization $organization)
    {
        $rows = [
            ['name', 'block', 'lot'],
            ['Dela Cruz Residence', '1', '1'],
            ['Reyes Residence', '1', '2'],
            ['Santos Residence', '2', '5'],
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, 'households-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(Request $request, Organization $organization, HouseholdCsvImporter $importer)
    {
        $validated = $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $result = $importer->import($organization, $validated['csv']);

        if (! $result->successful()) {
            return back()->withErrors(['csv' => implode(' ', $result->errors)])->withInput();
        }

        AuditLog::record($organization, 'household.imported', $request->user(), null, ['imported' => $result->imported]);

        return redirect()
            ->route('households.index', $organization)
            ->with('success', "{$result->imported} households imported successfully.");
    }
}
