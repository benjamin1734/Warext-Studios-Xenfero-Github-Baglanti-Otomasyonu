<?php

namespace Warext\GitHubSync\Job;

use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;
use Warext\GitHubSync\Service\GitHub\RepositorySynchronizer;
use XF\Job\AbstractJob;

class SyncRepositories extends AbstractJob
{
    public function run($maxRunTime): \XF\Job\JobResult
    {
        $connectionId = (int)($this->data['connection_id'] ?? 0);
        $connection = \XF::em()->find('Warext\\GitHubSync:Connection', $connectionId);
        if (!$connection || !$connection->active)
        {
            return $this->complete();
        }

        $client = new ApiClient(new JwtFactory(), new CredentialProvider());
        (new RepositorySynchronizer($client))->synchronize($connection);
        return $this->complete();
    }

    public function getStatusMessage(): string
    {
        return 'Synchronizing GitHub repositories...';
    }

    public function canCancel(): bool { return true; }
    public function canTriggerByChoice(): bool { return false; }
}
