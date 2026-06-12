<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SoftwareAssignment;
use Illuminate\Http\Request;

class SoftwareController extends Controller
{
    public function index(Request $request)
    {
        $query = SoftwareAssignment::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['license.software', 'license.supplier'])
            ->latest('assigned_date');

        if ($request->filled('search')) {
            $query->whereHas('license.software', fn($q) =>
                $q->where('name', 'like', '%'.$request->search.'%')
            );
        }

        $assignments = $query->paginate(20)->withQueryString();

        return view('staff.software.index', compact('assignments'));
    }
}
