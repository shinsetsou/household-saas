<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function show(Organization $organization): View
    {
        return view('organizations.settings.general', compact('organization'));
    }
}
