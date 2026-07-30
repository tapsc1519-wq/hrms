<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

final class ErpSsoTicket
{
    public function issue(User $user): string
    {
        $secret = (string) config('niyantron.products.erp.sso_secret');
        if (strlen($secret) < 32 || ! $user->organization) {
            throw new RuntimeException('ERP SSO is not configured.');
        }
        $now = time();
        $payload = ['iss' => 'niyantron-platform', 'aud' => 'niyantron-erp', 'jti' => (string) Str::uuid(), 'iat' => $now, 'exp' => $now + 60,
            'organization' => ['id' => (string) $user->organization->getKey(), 'name' => $user->organization->name],
            'user' => ['id' => (string) $user->getKey(), 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'must_change_password' => (bool) $user->must_change_password],
        ];
        $body = $this->encode(json_encode($payload, JSON_THROW_ON_ERROR));

        return $body.'.'.$this->encode(hash_hmac('sha256', $body, $secret, true));
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
