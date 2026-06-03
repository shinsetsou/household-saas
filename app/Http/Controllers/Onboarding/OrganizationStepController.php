<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\OnboardingOrganizationRequest;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationStepController extends Controller
{
    public function create(): View
    {
        return view('onboarding.organization');
    }

    public function store(OnboardingOrganizationRequest $request, OnboardingService $service): RedirectResponse
    {
        $organization = $service->createOrganizationForUser(
            $request->user(),
            $request->validated(),
        );

        $request->session()->put('onboarding.organization_id', $organization->id);

        return redirect()->route('onboarding.households.create');
    }
}
