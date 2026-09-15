<?php

namespace Warext\GitHubSync\Repository;

use XF\Mvc\Entity\Repository;

class Template extends Repository
{
    public function findActiveForEvent(string $event)
    {
        return $this->finder('Warext\\GitHubSync:Template')
            ->where('active', 1)
            ->whereOr([['event', $event], ['event', '*']])
            ->order('title', 'ASC');
    }
}
