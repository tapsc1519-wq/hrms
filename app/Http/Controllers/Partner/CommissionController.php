<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerCommission;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $partner = auth()->user()->partner;
        abort_if(!$partner, 403, 'Your partner account is not linked yet.');

        $query = PartnerCommission::with(['product', 'organization'])
            ->where('partner_id', $partner->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('partner.commissions.index', [
            'partner' => $partner,
            'commissions' => $query->paginate(20)->withQueryString(),
            'statuses' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'paid' => 'Paid',
                'cancelled' => 'Cancelled',
            ],
        ]);
    }
}
