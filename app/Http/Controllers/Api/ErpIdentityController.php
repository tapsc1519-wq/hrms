<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrganizationProductSubscription;
use App\Models\Product;
use App\Models\User;
use App\Support\ErpSsoTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class ErpIdentityController extends Controller
{
    public function __invoke(Request $request, ErpSsoTicket $tickets): JsonResponse
    {
        $secret = (string) config('niyantron.products.erp.sso_secret');
        $provided = (string) $request->header('X-Niyantron-Client-Secret');
        abort_unless(strlen($secret) >= 32 && hash_equals($secret, $provided), 401, 'Unauthenticated product client.');

        $credentials = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:1024']]);
        $user = User::with('organization')->whereRaw('LOWER(email) = ?', [strtolower($credentials['email'])])->where('status', 'active')->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->organization_id) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        $product = Product::where('slug', 'erp')->where('status', 'active')->first();
        $subscription = $product ? OrganizationProductSubscription::where('organization_id', $user->organization_id)->where('product_id', $product->id)->first() : null;
        if (! $subscription || ! in_array($subscription->status, ['active', 'trial'], true)
            || ($subscription->status === 'trial' && $subscription->trial_ends_at?->isPast())
            || ($subscription->status === 'active' && $subscription->subscription_ends_at?->isPast())) {
            return response()->json(['message' => 'ERP is not active for your organization. Contact your administrator.'], 403);
        }

        $user->update(['last_login_at' => now()]);

        return response()->json(['ticket' => $tickets->issue($user)]);
    }
}
