<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Entity\Repository;

final class UserMapper
{
    public function resolveXfUserId(Repository $repository, string $githubLogin, int $fallback): int
    {
        $login = mb_strtolower(trim($githubLogin));
        if ($login === '') return $fallback;

        foreach ([
            ['repository_id' => (int)$repository->repository_id],
            ['repository_id' => 0, 'connection_id' => (int)$repository->connection_id],
            ['repository_id' => 0, 'connection_id' => 0]
        ] as $scope)
        {
            $finder = \XF::finder('Warext\\GitHubSync:UserMapping')
                ->where('active', 1)
                ->where('github_login', $login);
            foreach ($scope as $field => $value) $finder->where($field, $value);
            $match = $finder->fetchOne();
            if ($match && (int)$match->xf_user_id > 0) return (int)$match->xf_user_id;
        }
        return $fallback;
    }
}
