<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class VendorController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.suppliers.index');
    }

    public function create()
    {
        return redirect()->route('admin.suppliers.create');
    }

    public function show()
    {
        return redirect()->route('admin.suppliers.index');
    }

    public function edit()
    {
        return redirect()->route('admin.suppliers.index');
    }
}
