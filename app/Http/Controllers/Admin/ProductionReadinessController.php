<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ProductionReadinessService;

class ProductionReadinessController extends Controller
{
    public function index()
    {
        $readiness = ProductionReadinessService::forOrganization(auth()->user());

        return view('admin.production-readiness.index', compact('readiness'));
    }
}
