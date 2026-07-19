<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;

final class ErpLogoutTicketVerifier
{
    public function verify(string $ticket): void
    {
        [$body, $signature] = array_pad(explode('.', $ticket, 2), 2, null);
        $secret = (string) config('niyantron.products.erp.sso_secret');
        if (! $body || ! $signature || strlen($secret) < 32) {
            throw new AuthenticationException('ERP logout is not configured.');
        }

        $expected = $this->encode(hash_hmac('sha256', $body, $secret, true));
        if (! hash_equals($expected, $signature)) {
            throw new AuthenticationException('Invalid ERP logout ticket.');
        }

        $payload = json_decode($this->decode($body), true, flags: JSON_THROW_ON_ERROR);
        if (($payload['iss'] ?? null) !== 'niyantron-erp'
            || ($payload['aud'] ?? null) !== 'niyantron-platform-logout'
            || ! isset($payload['jti'], $payload['exp'])
            || (int) $payload['exp'] < time()) {
            throw new AuthenticationException('Invalid or expired ERP logout ticket.');
        }
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
