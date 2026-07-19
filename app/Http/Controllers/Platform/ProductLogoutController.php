<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Support\ErpLogoutTicketVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ProductLogoutController extends Controller
{
    public function __invoke(Request $request, ErpLogoutTicketVerifier $tickets): RedirectResponse
    {
        $tickets->verify($request->validate(['ticket' => ['required', 'string', 'max:8192']])['ticket']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(rtrim((string) config('niyantron.products.erp.url'), '/').'/login?logged_out=1');
    }
}
