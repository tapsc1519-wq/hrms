<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\OnboardingWizardService;

class WelcomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $wizard = OnboardingWizardService::forOrganization($user);

        return view('admin.welcome.index', compact('user', 'wizard'));
    }

    public function dismiss()
    {
        session()->forget('admin_first_login');

        return redirect()->route('admin.dashboard')
            ->with('success', 'Welcome completed. You can open Setup Wizard anytime from Operations.');
    }
}
