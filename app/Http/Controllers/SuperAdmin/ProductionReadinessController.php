<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\ProductionReadinessService;

class ProductionReadinessController extends Controller
{
    public function index()
    {
        $readiness = ProductionReadinessService::forPlatform();

        return view('super-admin.production-readiness.index', compact('readiness'));
    }
}
