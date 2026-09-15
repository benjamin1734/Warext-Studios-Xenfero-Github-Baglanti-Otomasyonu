<?php

namespace Warext\GitHubSync\Service\GitHub;

use Warext\GitHubSync\Entity\Connection;

final class RepositorySynchronizer
{
    public function __construct(private ApiClient $client) {}

    /** @return array{created:int,updated:int,deactivated:int,total:int} */
    public function synchronize(Connection $connection): array
    {
        $repositories = $this->client->installationRepositories($connection);
        $seen = [];
        $created = 0;
        $updated = 0;

        foreach ($repositories as $repoData)
        {
            $githubId = (int)($repoData['id'] ?? 0);
            if ($githubId <= 0)
            {
                continue;
            }
            $seen[$githubId] = true;

            $repo = \XF::finder('Warext\\GitHubSync:Repository')
                ->where('github_repository_id', $githubId)
                ->fetchOne();
            $isNew = !$repo;
            if (!$repo)
            {
                $repo = \XF::em()->create('Warext\\GitHubSync:Repository');
                $repo->github_repository_id = $githubId;
                $repo->created_date = \XF::$time;
            }

            $repo->connection_id = (int)$connection->connection_id;
            $repo->full_name = (string)($repoData['full_name'] ?? '');
            $repo->owner_name = (string)($repoData['owner']['login'] ?? '');
            $repo->repo_name = (string)($repoData['name'] ?? '');
            $repo->default_branch = (string)($repoData['default_branch'] ?? 'main');
            $repo->html_url = (string)($repoData['html_url'] ?? '');
            $repo->is_private = (bool)($repoData['private'] ?? false);
            $repo->is_archived = (bool)($repoData['archived'] ?? false);
            $repo->active = true;
            $repo->updated_date = \XF::$time;
            $repo->save();

            $isNew ? $created++ : $updated++;
        }

        $deactivated = 0;
        $existing = \XF::finder('Warext\\GitHubSync:Repository')
            ->where('connection_id', (int)$connection->connection_id)
            ->where('active', 1)
            ->fetch();
        foreach ($existing as $repo)
        {
            if (!isset($seen[(int)$repo->github_repository_id]))
            {
                $repo->active = false;
                $repo->updated_date = \XF::$time;
                $repo->save();
                $deactivated++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'deactivated' => $deactivated,
            'total' => count($repositories)
        ];
    }
}
