<?php

namespace Warext\GitHubSync\Service\GitHub;

use RuntimeException;
use Warext\GitHubSync\Entity\Connection;

final class ApiClient
{
    public const API_BASE = 'https://api.github.com';
    public const API_VERSION = '2026-03-10';

    public function __construct(
        private JwtFactory $jwtFactory,
        private CredentialProvider $credentialProvider
    ) {}

    public function installationToken(Connection $connection): string
    {
        if ((int)$connection->installation_id <= 0)
        {
            throw new RuntimeException('GitHub App installation ID is not configured.');
        }

        $jwt = $this->jwtFactory->create(
            (int)$connection->app_id,
            $this->credentialProvider->privateKey($connection)
        );

        $data = $this->requestJson(
            'POST',
            '/app/installations/' . (int)$connection->installation_id . '/access_tokens',
            $jwt,
            []
        );

        $token = (string)($data['token'] ?? '');
        if ($token === '')
        {
            throw new RuntimeException('GitHub did not return an installation access token.');
        }

        return $token;
    }

    public function installation(Connection $connection): array
    {
        $jwt = $this->jwtFactory->create(
            (int)$connection->app_id,
            $this->credentialProvider->privateKey($connection)
        );

        return $this->requestJson(
            'GET',
            '/app/installations/' . (int)$connection->installation_id,
            $jwt
        );
    }

    /** @return array<int, array<string,mixed>> */
    public function installationRepositories(Connection $connection): array
    {
        $token = $this->installationToken($connection);
        $repositories = [];
        $page = 1;

        do
        {
            $data = $this->requestJson(
                'GET',
                '/installation/repositories?per_page=100&page=' . $page,
                $token
            );
            $batch = $data['repositories'] ?? [];
            if (!is_array($batch))
            {
                $batch = [];
            }
            foreach ($batch as $repository)
            {
                if (is_array($repository))
                {
                    $repositories[] = $repository;
                }
            }
            $page++;
        }
        while (count($batch) === 100 && $page <= 100);

        return $repositories;
    }

    public function requestInstallation(Connection $connection, string $method, string $path, array $json = []): array
    {
        return $this->requestJson($method, $path, $this->installationToken($connection), $json);
    }

    public function createIssue(Connection $connection, string $owner, string $repo, string $title, string $body, array $labels = []): array
    {
        $payload = [
            'title' => $title,
            'body' => $body
        ];
        if ($labels !== []) $payload['labels'] = array_values(array_unique(array_map('strval', $labels)));
        return $this->requestInstallation($connection, 'POST', $this->repoPath($owner, $repo) . '/issues', $payload);
    }

    public function updateIssue(Connection $connection, string $owner, string $repo, int $number, array $changes): array
    {
        return $this->requestInstallation($connection, 'PATCH', $this->repoPath($owner, $repo) . '/issues/' . $number, $changes);
    }

    public function createIssueComment(Connection $connection, string $owner, string $repo, int $number, string $body): array
    {
        return $this->requestInstallation($connection, 'POST', $this->repoPath($owner, $repo) . '/issues/' . $number . '/comments', [
            'body' => $body
        ]);
    }

    public function updateIssueComment(Connection $connection, string $owner, string $repo, int $commentId, string $body): array
    {
        return $this->requestInstallation($connection, 'PATCH', $this->repoPath($owner, $repo) . '/issues/comments/' . $commentId, [
            'body' => $body
        ]);
    }

    public function deleteIssueComment(Connection $connection, string $owner, string $repo, int $commentId): void
    {
        $this->requestInstallation($connection, 'DELETE', $this->repoPath($owner, $repo) . '/issues/comments/' . $commentId);
    }

    private function repoPath(string $owner, string $repo): string
    {
        return '/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo);
    }

    private function requestJson(string $method, string $path, string $bearerToken, array $json = []): array
    {
        $client = \XF::app()->http()->client();
        $options = [
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'Authorization' => 'Bearer ' . $bearerToken,
                'X-GitHub-Api-Version' => self::API_VERSION,
                'User-Agent' => 'Warext-GitHub-Sync/0.5'
            ],
            'http_errors' => false,
            'timeout' => 20,
            'connect_timeout' => 8
        ];
        if ($json !== [] || strtoupper($method) !== 'GET')
        {
            $options['json'] = $json;
        }

        $response = $client->request(strtoupper($method), self::API_BASE . $path, $options);
        $status = (int)$response->getStatusCode();
        $body = (string)$response->getBody();
        $data = $body !== '' ? json_decode($body, true) : [];
        if (!is_array($data))
        {
            $data = [];
        }

        if ($status < 200 || $status >= 300)
        {
            $message = (string)($data['message'] ?? ('HTTP ' . $status));
            throw new RuntimeException('GitHub API request failed: ' . $message . ' (' . $status . ')');
        }

        return $data;
    }
}
