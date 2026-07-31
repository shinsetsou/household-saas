<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HouseholdController extends Controller
{
    public function index(Request $request, Organization $organization)
    {
        if (! empty($request->block) && ! empty($request->lot)) {

            $household = Household::where('block', $request->block)
                ->where('lot', $request->lot)
                ->where('organization_id', $organization->id)
                ->first();

            if (empty($household)) {
                return redirect()->back()->with('organization', $organization);
            }

            $itemsPerPage = $request->input('itemsPerPage', 10); // Default to 10 items per page
            $contributions = $household->contributions()->orderBy('created_at', 'desc')->paginate($itemsPerPage);

            // Public block+lot lookup route also renders the show view, so it
            // needs the same household stats that the dedicated show() method passes.
            return view('households.show', array_merge(
                compact('household', 'contributions', 'itemsPerPage', 'organization'),
                $this->totalsFor($household, $organization),
                $this->statsFor($household),
            ));
        }

        if (! $request->user()) {
            return redirect()->route('login');
        }

        abort_unless($request->user()->belongsToOrganization($organization), 403);

        $households = Household::forOrganization($organization)
            ->orderBy('block')
            ->orderBy('lot')
            ->paginate($request->input('itemsPerPage', 25));

        return view('households.index', compact('households', 'organization'));
    }

    public function create(Organization $organization)
    {
        return view('households.create', compact('organization'));
    }

    public function store(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'block' => 'required|integer|min:1',
            'lot' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $organization) {
            $locked = Organization::whereKey($organization->id)->lockForUpdate()->first();

            $limit = $locked->householdLimit();

            if ($limit !== null && $locked->households()->count() >= $limit) {
                throw ValidationException::withMessages([
                    'name' => $locked->isOnTrial()
                        ? "Your free trial allows up to {$limit} households. Upgrade to a paid plan to add more."
                        : "Your current plan allows up to {$limit} households. Upgrade to add more.",
                ]);
            }

            $locked->households()->create($validated);
        });

        return redirect()->route('households.index', $organization)->with('success', 'Household created successfully.');
    }

    public function show(Organization $organization, Household $household, Request $request)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $itemsPerPage = $request->input('itemsPerPage', 10); // Default to 10 items per page
        $contributions = $household->contributions()->orderBy('created_at', 'desc')->paginate($itemsPerPage);

        return view('households.show', array_merge(
            compact('household', 'contributions', 'itemsPerPage', 'organization'),
            $this->totalsFor($household, $organization),
            $this->statsFor($household),
        ));
    }

    public function edit(Organization $organization, Household $household)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        return view('households.edit', compact('household', 'organization'));
    }

    public function update(Request $request, Organization $organization, Household $household)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'block' => 'required|integer|min:1',
            'lot' => 'required|integer|min:1',
        ]);

        $household->update($request->only(['name', 'block', 'lot']));

        return redirect()->route('households.index', $organization)->with('success', 'Household updated successfully.');
    }

    public function destroy(Organization $organization, Household $household)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $household->delete();

        return redirect()->route('households.index', $organization)->with('success', 'Household deleted successfully.');
    }

    private function totalsFor(Household $household, Organization $organization): array
    {
        $ttl = now()->addMinutes(5);

        $totalContributions = Cache::remember(
            "household:{$household->id}:contributions_sum",
            $ttl,
            fn () => (float) $household->contributions()->sum('amount')
        );

        $totalExpenses = Cache::remember(
            "organization:{$organization->id}:expenses_sum",
            $ttl,
            fn () => (float) Expense::where('organization_id', $organization->id)->sum('amount')
        );

        return compact('totalContributions', 'totalExpenses');
    }

    /**
     * Per-household verification stats used by the show view's stat tiles.
     * Shared by HouseholdController::show and the public block+lot lookup
     * path inside index(), both of which render households.show.
     */
    private function statsFor(Household $household): array
    {
        // `max(created_at)` returns the raw DB string in some drivers (e.g.
        // MySQL when the column isn't selected through the cast), so wrap it
        // in Carbon::parse so the view can call ->diffForHumans() safely.
        $lastPaidRaw = $household->contributions()
            ->where('verification_status', 'verified')
            ->max('created_at');

        return [
            'verifiedCount' => $household->contributions()
                ->where('verification_status', 'verified')
                ->count(),
            'pendingCount' => $household->contributions()
                ->where('verification_status', 'pending')
                ->count(),
            'lastPaidAt' => $lastPaidRaw ? Carbon::parse($lastPaidRaw) : null,
        ];
    }
}
