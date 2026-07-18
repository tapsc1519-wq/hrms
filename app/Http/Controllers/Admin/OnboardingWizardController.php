<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\OnboardingWizardService;

class OnboardingWizardController extends Controller
{
    public function index()
    {
        $wizard = OnboardingWizardService::forOrganization(auth()->user());

        return view('admin.onboarding-wizard.index', compact('wizard'));
    }
}
