<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class ErpUserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $secret = (string) config('niyantron.products.erp.sso_secret');
        $provided = (string) $request->header('X-Niyantron-Client-Secret');
        abort_unless(strlen($secret) >= 32 && hash_equals($secret, $provided), 401, 'Unauthenticated product client.');

        $data = $request->validate([
            'organization_id' => ['required', 'integer'],
            'actor_user_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'temporary_password' => ['required', 'string', 'min:12', 'max:80'],
        ]);

        $organization = Organization::query()->whereKey($data['organization_id'])->where('status', 'active')->first();
        $actor = User::query()->whereKey($data['actor_user_id'])->where('status', 'active')->first();
        abort_unless($organization && $actor && (int) $actor->organization_id === (int) $organization->getKey() && $actor->role === 'admin', 403, 'Only an active organization administrator can create ERP users.');

        $product = Product::query()->where('slug', 'erp')->where('status', 'active')->first();
        $subscription = $product
            ? OrganizationProductSubscription::query()->where('organization_id', $organization->getKey())->where('product_id', $product->getKey())->first()
            : null;
        abort_unless(
            $subscription
            && in_array($subscription->status, ['active', 'trial'], true)
            && ! ($subscription->status === 'trial' && $subscription->trial_ends_at?->isPast())
            && ! ($subscription->status === 'active' && $subscription->subscription_ends_at?->isPast()),
            403,
            'ERP is not active for this organization.'
        );

        $email = strtolower($data['email']);
        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return response()->json(['message' => 'This email address already belongs to a Niyantron identity.'], 422);
        }

        $user = User::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($data['temporary_password']),
            'must_change_password' => true,
            'role' => 'staff',
            'status' => 'active',
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
        ]);

        return response()->json([
            'user' => [
                'id' => (string) $user->getKey(),
                'organization_id' => (string) $organization->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'login_url' => rtrim((string) config('niyantron.products.erp.url'), '/').'/login',
        ], 201);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $secret = (string) config('niyantron.products.erp.sso_secret');
        $provided = (string) $request->header('X-Niyantron-Client-Secret');
        abort_unless(strlen($secret) >= 32 && hash_equals($secret, $provided), 401, 'Unauthenticated product client.');

        $data = $request->validate([
            'organization_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'password' => ['required', 'string', 'min:12', 'max:80'],
        ]);
        $user = User::query()->whereKey($data['user_id'])->where('organization_id', $data['organization_id'])->where('status', 'active')->first();
        abort_unless($user, 404, 'ERP identity was not found.');
        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'invitation_accepted_at' => now(),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }
}
