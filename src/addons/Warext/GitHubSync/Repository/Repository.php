<?php

namespace Warext\GitHubSync\Repository;

use XF\Mvc\Entity\Repository as EntityRepository;

class Repository extends EntityRepository
{
    public function findActiveForConnection(int $connectionId)
    {
        return $this->finder('Warext\\GitHubSync:Repository')
            ->where('connection_id', $connectionId)
            ->where('active', 1)
            ->order('full_name', 'ASC');
    }
}
