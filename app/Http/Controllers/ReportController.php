<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Expense;
use App\Models\Organization;
use App\Services\DelinquencyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function contributions(Request $request, Organization $organization)
    {
        $contributions = $this->contributionQuery($request, $organization)->paginate(25);

        return view('reports.contributions', compact('organization', 'contributions'));
    }

    public function contributionsPdf(Request $request, Organization $organization)
    {
        $contributions = $this->contributionQuery($request, $organization)->get();

        return Pdf::loadView('reports.pdf.contributions', compact('organization', 'contributions'))
            ->download('contributions-report.pdf');
    }

    public function contributionsExcel(Request $request, Organization $organization): StreamedResponse
    {
        $contributions = $this->contributionQuery($request, $organization)->get();

        return $this->csv('contributions-report.csv', [
            ['Household', 'Amount', 'Payment Status', 'Verification Status', 'Date'],
            ...$contributions->map(fn ($contribution) => [
                $contribution->household->name,
                $contribution->amount,
                $contribution->status,
                $contribution->verification_status,
                $contribution->created_at->format('Y-m-d'),
            ])->all(),
        ]);
    }

    public function expenses(Request $request, Organization $organization)
    {
        $expenses = $this->expenseQuery($request, $organization)->paginate(25);

        return view('reports.expenses', compact('organization', 'expenses'));
    }

    public function expensesPdf(Request $request, Organization $organization)
    {
        $expenses = $this->expenseQuery($request, $organization)->get();

        return Pdf::loadView('reports.pdf.expenses', compact('organization', 'expenses'))
            ->download('expenses-report.pdf');
    }

    public function expensesExcel(Request $request, Organization $organization): StreamedResponse
    {
        $expenses = $this->expenseQuery($request, $organization)->get();

        return $this->csv('expenses-report.csv', [
            ['Description', 'Amount', 'Date'],
            ...$expenses->map(fn ($expense) => [
                $expense->description,
                $expense->amount,
                $expense->created_at->format('Y-m-d'),
            ])->all(),
        ]);
    }

    public function delinquency(Request $request, Organization $organization, DelinquencyService $service)
    {
        $rows = $service->forOrganization(
            $organization,
            (int) $request->input('year', now()->year),
            (int) $request->input('month', now()->month)
        );

        return view('reports.delinquency', compact('organization', 'rows'));
    }

    public function delinquencyCsv(Request $request, Organization $organization, DelinquencyService $service): StreamedResponse
    {
        $rows = $service->forOrganization(
            $organization,
            (int) $request->input('year', now()->year),
            (int) $request->input('month', now()->month)
        );

        return $this->csv('delinquency-report.csv', [
            ['Household', 'Block', 'Lot', 'Months Missing', 'Amount Due', 'Latest Payment Date'],
            ...collect($rows)->map(fn ($row) => [
                $row['household']->name,
                $row['household']->block,
                $row['household']->lot,
                implode('|', $row['months_missing']),
                $row['amount_due'],
                optional($row['latest_payment_date'])->format('Y-m-d'),
            ])->all(),
        ]);
    }

    private function contributionQuery(Request $request, Organization $organization)
    {
        return Contribution::forOrganization($organization)
            ->with('household')
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->month))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->year))
            ->when($request->filled('verification_status'), fn ($query) => $query->where('verification_status', $request->verification_status))
            ->when($request->filled('household_id'), fn ($query) => $query->where('household_id', $request->household_id))
            ->latest();
    }

    private function expenseQuery(Request $request, Organization $organization)
    {
        return Expense::forOrganization($organization)
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->month))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->year))
            ->latest();
    }

    private function csv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
