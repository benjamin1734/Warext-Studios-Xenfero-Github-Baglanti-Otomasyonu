<?php

namespace Warext\GitHubSync\Service\GitHub;

use RuntimeException;
use Warext\GitHubSync\Entity\Connection;

final class CredentialProvider
{
    public function privateKey(Connection $connection): string
    {
        $ref = trim((string)$connection->private_key_ref);
        if ($ref === '')
        {
            throw new RuntimeException('GitHub App private key reference is missing.');
        }

        return $this->resolveSecretRef($ref, 'privateKeys');
    }

    public function webhookSecret(Connection $connection): string
    {
        $ref = trim((string)$connection->secret_ref);
        if ($ref === '')
        {
            return '';
        }

        return $this->resolveSecretRef($ref, 'webhookSecrets');
    }

    private function resolveSecretRef(string $ref, string $bucket): string
    {
        $config = \XF::config('warextGitHubSync');
        if (!is_array($config))
        {
            return '';
        }

        $values = $config[$bucket] ?? [];
        if (!is_array($values))
        {
            return '';
        }

        return trim((string)($values[$ref] ?? ''));
    }
}
