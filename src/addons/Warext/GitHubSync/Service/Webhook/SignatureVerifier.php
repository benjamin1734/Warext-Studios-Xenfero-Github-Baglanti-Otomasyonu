<?php

namespace Warext\GitHubSync\Service\Webhook;

final class SignatureVerifier
{
    public function verify(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($secret === '' || $signatureHeader === '' || !str_starts_with($signatureHeader, 'sha256='))
        {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signatureHeader);
    }
    /** @param string[] $secrets */
    public function verifyAny(string $payload, string $signatureHeader, array $secrets): bool
    {
        foreach ($secrets as $secret)
        {
            if (is_string($secret) && $this->verify($payload, $signatureHeader, $secret))
            {
                return true;
            }
        }
        return false;
    }

}
