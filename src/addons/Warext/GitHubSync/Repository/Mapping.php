<?php

namespace Warext\GitHubSync\Repository;

use XF\Mvc\Entity\Repository;

class Mapping extends Repository
{
    public function findApplicable(int $repositoryId, string $event, string $action)
    {
        return $this->finder('Warext\\GitHubSync:Mapping')
            ->where('enabled', 1)
            ->whereOr([
                ['repository_id', $repositoryId],
                ['repository_id', 0]
            ])
            ->whereOr([
                ['event', $event],
                ['event', '*']
            ])
            ->whereOr([
                ['event_action', $action],
                ['event_action', '*']
            ])
            ->order('priority', 'ASC');
    }
}
