<?php

namespace Warext\GitHubSync\Service\Webhook;

use Warext\GitHubSync\Service\GitHub\CredentialProvider;

final class SecretResolver
{
    public function __construct(private CredentialProvider $credentials) {}

    /** @return string[] */
    public function resolveCandidates(string $payload): array
    {
        $secrets = [];

        // Bootstrap/global fallback supports initial installation and ping deliveries.
        $global = (new SecretProvider())->getGlobalSecret();
        if ($global !== '')
        {
            $secrets[] = $global;
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded))
        {
            return array_values(array_unique($secrets));
        }

        $githubRepositoryId = (int)($decoded['repository']['id'] ?? 0);
        if ($githubRepositoryId > 0)
        {
            $repository = \XF::finder('Warext\\GitHubSync:Repository')
                ->where('github_repository_id', $githubRepositoryId)
                ->fetchOne();
            if ($repository)
            {
                $connection = \XF::em()->find('Warext\\GitHubSync:Connection', (int)$repository->connection_id);
                if ($connection && $connection->active)
                {
                    $secret = $this->credentials->webhookSecret($connection);
                    if ($secret !== '')
                    {
                        array_unshift($secrets, $secret);
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($secrets, fn($s) => is_string($s) && $s !== '')));
    }
}
