<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Support\ErpSsoTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ErpLaunchController extends Controller
{
    public function __invoke(Request $request, ErpSsoTicket $tickets): RedirectResponse
    {
        $ticket = $tickets->issue($request->user()->loadMissing('organization'));
        return redirect()->away(rtrim((string) config('niyantron.products.erp.url'), '/').'/auth/platform/callback?ticket='.urlencode($ticket));
    }
}
