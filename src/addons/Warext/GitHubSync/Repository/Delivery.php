<?php

namespace Warext\GitHubSync\Repository;

use XF\Mvc\Entity\Repository;

class Delivery extends Repository
{
    public function findPendingForProcess()
    {
        return $this->finder('Warext\\GitHubSync:Delivery')
            ->where('status', ['received', 'failed', 'retry_waiting'])
            ->order('received_date', 'ASC');
    }
}
