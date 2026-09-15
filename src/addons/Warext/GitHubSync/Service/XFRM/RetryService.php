<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;
use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Service\GitHub\ApiClient as GitHubApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider as GitHubCredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;

final class RetryService
{
    private GitHubApiClient $github;
    private ReleaseSynchronizer $synchronizer;
    private HistoryLogger $history;

    public function __construct()
    {
        $this->github = new GitHubApiClient(new JwtFactory(), new GitHubCredentialProvider());
        $this->synchronizer = new ReleaseSynchronizer();
        $this->history = new HistoryLogger();
    }

    public function retry($record): array
    {
        $mapping = \XF::em()->find('Warext\\GitHubSync:Mapping', (int)$record->mapping_id);
        $repository = \XF::em()->find('Warext\\GitHubSync:Repository', (int)$record->repository_id);
        if (!$mapping || !$repository) throw new RuntimeException('XFRM retry mapping or repository no longer exists.');
        $connection = \XF::em()->find('Warext\\GitHubSync:Connection', (int)$repository->connection_id);
        if (!$connection) throw new RuntimeException('GitHub connection no longer exists.');

        $record->retry_count = (int)$record->retry_count + 1;
        $record->last_attempt_date = \XF::$time;
        $record->status = 'retrying';
        $record->last_error = '';
        $record->save();
        $this->history->log($record, 'manual_retry', 'started', 'Manual XFRM retry started.');

        try
        {
            $release = $this->github->getRelease($connection, (string)$repository->owner_name, (string)$repository->repo_name, (int)$record->github_release_id);
            $payload = [
                'action' => 'published',
                'release' => $release,
                'repository' => [
                    'id' => (int)$repository->github_repository_id,
                    'full_name' => (string)$repository->full_name
                ]
            ];
            $event = new WebhookEvent(
                'manual-xfrm-retry-' . (int)$record->xfrm_release_id . '-' . \XF::$time,
                'release',
                'published',
                (int)$repository->github_repository_id,
                (string)$repository->full_name,
                $payload
            );
            $result = $this->synchronizer->synchronize($mapping, $repository, $event, hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
            $fresh = \XF::em()->find('Warext\\GitHubSync:XfrmRelease', (int)$record->xfrm_release_id);
            if ($fresh) $this->history->log($fresh, 'manual_retry', 'completed', (string)($result['message'] ?? 'Retry completed.'));
            return $result;
        }
        catch (\Throwable $e)
        {
            $fresh = \XF::em()->find('Warext\\GitHubSync:XfrmRelease', (int)$record->xfrm_release_id) ?: $record;
            $fresh->status = 'failed';
            $fresh->last_error = mb_substr($e->getMessage(), 0, 1000);
            $fresh->last_attempt_date = \XF::$time;
            $fresh->save();
            $this->history->log($fresh, 'manual_retry', 'failed', $e->getMessage());
            throw $e;
        }
    }
}
