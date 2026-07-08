<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\OrganizationProductSubscription;
use App\Models\PartnerCommission;
use App\Models\PartnerLead;

class DashboardController extends Controller
{
    public function index()
    {
        $partner = auth()->user()->partner;
        abort_if(!$partner, 403, 'Your partner account is not linked yet.');

        $stats = [
            'open_leads' => PartnerLead::where('partner_id', $partner->id)->whereNotIn('stage', ['won', 'lost'])->count(),
            'converted_leads' => PartnerLead::where('partner_id', $partner->id)->where('stage', 'won')->count(),
            'subscriptions' => OrganizationProductSubscription::where('partner_id', $partner->id)->count(),
            'pending_commission' => PartnerCommission::where('partner_id', $partner->id)->where('status', 'pending')->sum('commission_amount'),
            'approved_commission' => PartnerCommission::where('partner_id', $partner->id)->where('status', 'approved')->sum('commission_amount'),
            'paid_commission' => PartnerCommission::where('partner_id', $partner->id)->where('status', 'paid')->sum('commission_amount'),
        ];

        $recentLeads = PartnerLead::with('product')
            ->where('partner_id', $partner->id)
            ->latest()
            ->take(6)
            ->get();

        $recentCommissions = PartnerCommission::with(['product', 'organization'])
            ->where('partner_id', $partner->id)
            ->latest()
            ->take(6)
            ->get();

        return view('partner.dashboard', compact('partner', 'stats', 'recentLeads', 'recentCommissions'));
    }
}
