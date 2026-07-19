<?php

namespace Tests\Unit;

use App\Support\ErpLogoutTicketVerifier;
use Illuminate\Auth\AuthenticationException;
use Tests\TestCase;

final class ErpLogoutTicketVerifierTest extends TestCase
{
    public function test_it_accepts_a_valid_short_lived_erp_logout_ticket(): void
    {
        config()->set('niyantron.products.erp.sso_secret', str_repeat('s', 32));
        app(ErpLogoutTicketVerifier::class)->verify($this->ticket(time() + 60));
        $this->addToAssertionCount(1);
    }

    public function test_it_rejects_an_expired_logout_ticket(): void
    {
        config()->set('niyantron.products.erp.sso_secret', str_repeat('s', 32));
        $this->expectException(AuthenticationException::class);
        app(ErpLogoutTicketVerifier::class)->verify($this->ticket(time() - 1));
    }

    private function ticket(int $expiresAt): string
    {
        $body = $this->encode(json_encode(['iss' => 'niyantron-erp', 'aud' => 'niyantron-platform-logout', 'jti' => 'test-ticket', 'exp' => $expiresAt], JSON_THROW_ON_ERROR));

        return $body.'.'.$this->encode(hash_hmac('sha256', $body, str_repeat('s', 32), true));
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
