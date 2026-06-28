<?php

namespace App\Services;

use App\Models\AgentCommand;
use App\Models\AgentSigningKey;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class AgentCommandSigningService
{
    public function keyFor(int $organizationId): AgentSigningKey
    {
        return AgentSigningKey::firstOrCreate(['organization_id' => $organizationId], function () use ($organizationId) {
            $options = $this->opensslOptions();
            $resource = openssl_pkey_new($options);
            if (! $resource || ! openssl_pkey_export($resource, $privateKey, null, $options)) throw new RuntimeException('Unable to generate the agent signing key.');
            $details = openssl_pkey_get_details($resource);
            if (! isset($details['rsa']['n'], $details['rsa']['e'])) throw new RuntimeException('Unable to read the generated RSA key.');
            $publicXml = '<RSAKeyValue><Modulus>'.base64_encode($details['rsa']['n']).'</Modulus><Exponent>'.base64_encode($details['rsa']['e']).'</Exponent></RSAKeyValue>';
            return [
                'organization_id' => $organizationId,
                'encrypted_private_key' => Crypt::encryptString($privateKey),
                'public_key_xml' => $publicXml,
                'fingerprint' => hash('sha256', $publicXml),
            ];
        });
    }

    public function signedPayload(AgentCommand $command): array
    {
        $key = $this->keyFor($command->organization_id);
        $issuedAt = now()->timestamp;
        $expiresAt = ($command->expires_at ?: now()->addHours(24))->timestamp;
        $payloadJson = json_encode($command->payload ?? new \stdClass(), JSON_UNESCAPED_SLASHES);
        $payloadBase64 = base64_encode($payloadJson);
        $canonical = implode('|', [$command->command_uuid, $command->deviceAgent->device_uuid, $command->command_type, $issuedAt, $expiresAt, $payloadBase64]);
        $privateKey = Crypt::decryptString($key->encrypted_private_key);
        if (! openssl_sign($canonical, $signature, $privateKey, OPENSSL_ALGO_SHA256)) throw new RuntimeException('Unable to sign the agent command.');
        return [
            'command_uuid' => $command->command_uuid,
            'device_uuid' => $command->deviceAgent->device_uuid,
            'command_type' => $command->command_type,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'payload_base64' => $payloadBase64,
            'signature' => base64_encode($signature),
        ];
    }

    private function opensslOptions(): array
    {
        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $candidates = array_filter([
            config('agent.openssl_config'),
            dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            base_path('vendor/phpseclib/phpseclib/phpseclib/openssl.cnf'),
        ]);
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $options['config'] = $candidate;
                break;
            }
        }
        return $options;
    }
}
