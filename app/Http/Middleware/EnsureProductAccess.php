<?php

namespace App\Http\Middleware;

use App\Models\OrganizationProductSubscription;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductAccess
{
    public function handle(Request $request, Closure $next, string $productSlug): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if (!$user->organization_id) {
            abort(403, 'Your account is not linked to an organization.');
        }

        $product = Product::where('slug', $productSlug)->first();

        if (!$product) {
            abort(402, 'This product is not configured on the Niyantron platform.');
        }

        $subscription = OrganizationProductSubscription::where('organization_id', $user->organization_id)
            ->where('product_id', $product->id)
            ->first();

        if (!$subscription) {
            abort(402, $product->name . ' is not provisioned for your organization. Please contact your administrator.');
        }

        $message = $this->accessBlockMessage($subscription, $product->name);

        if ($message !== null) {
            abort(402, $message);
        }

        return $next($request);
    }

    private function accessBlockMessage(OrganizationProductSubscription $subscription, string $productName): ?string
    {
        if ($subscription->status === 'trial') {
            if ($subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
                return 'Your ' . $productName . ' trial has expired. Please ask your administrator to activate the subscription.';
            }

            return null;
        }

        if ($subscription->status === 'active') {
            if ($subscription->subscription_ends_at && $subscription->subscription_ends_at->isPast()) {
                return 'Your ' . $productName . ' subscription has expired. Please ask your administrator to renew it.';
            }

            return null;
        }

        return match ($subscription->status) {
            'overdue' => 'Your ' . $productName . ' subscription payment is overdue. Please contact your administrator.',
            'suspended' => 'Your ' . $productName . ' subscription is suspended. Please contact your administrator.',
            'cancelled' => 'Your ' . $productName . ' subscription is cancelled. Please contact your administrator.',
            default => 'Your organization does not currently have access to ' . $productName . '.',
        };
    }
}
