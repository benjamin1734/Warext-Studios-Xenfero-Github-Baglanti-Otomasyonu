<?php

namespace Warext\GitHubSync\Service\GitHub;

use RuntimeException;

final class JwtFactory
{
    public function create(int $appId, string $privateKeyPem, ?int $now = null): string
    {
        if ($appId <= 0)
        {
            throw new RuntimeException('GitHub App ID is not configured.');
        }
        if (trim($privateKeyPem) === '')
        {
            throw new RuntimeException('GitHub App private key is not configured.');
        }

        $now ??= time();
        // GitHub recommends allowing for small clock drift and JWTs must be short lived.
        $payload = [
            'iat' => $now - 60,
            'exp' => $now + 540,
            'iss' => (string)$appId
        ];

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64Url(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            $this->base64Url(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))
        ];
        $signingInput = implode('.', $segments);

        $key = openssl_pkey_get_private($privateKeyPem);
        if ($key === false)
        {
            throw new RuntimeException('Invalid GitHub App private key.');
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256))
        {
            throw new RuntimeException('Unable to sign GitHub App JWT.');
        }

        $segments[] = $this->base64Url($signature);
        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
