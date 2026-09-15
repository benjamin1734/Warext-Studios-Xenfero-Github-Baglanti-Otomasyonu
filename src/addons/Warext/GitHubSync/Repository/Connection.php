<?php

namespace Warext\GitHubSync\Repository;

use XF\Mvc\Entity\Repository;

class Connection extends Repository
{
    public function findActive()
    {
        return $this->finder('Warext\\GitHubSync:Connection')
            ->where('active', 1)
            ->order('title', 'ASC');
    }
}
