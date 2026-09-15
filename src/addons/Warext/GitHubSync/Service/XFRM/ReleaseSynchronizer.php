<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;
use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

final class ReleaseSynchronizer
{
    private ApiClient $api;
    private Capability $capability;
    private DownloadResolver $downloads;

    public function __construct()
    {
        $this->api = new ApiClient(new CredentialProvider());
        $this->capability = new Capability();
        $this->downloads = new DownloadResolver();
    }

    public function enabled(Mapping $mapping, WebhookEvent $event): bool
    {
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        return $event->event === 'release'
            && !empty($target['xfrm_enabled'])
            && (int)($target['xfrm_resource_id'] ?? 0) > 0;
    }

    public function mode(Mapping $mapping): string
    {
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        return (string)($target['xfrm_mode'] ?? 'alongside');
    }

    public function synchronize(Mapping $mapping, Repository $repository, WebhookEvent $event, string $payloadHash): array
    {
        if (!$this->enabled($mapping, $event)) return ['processed' => false, 'message' => ''];

        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        $resourceId = (int)$target['xfrm_resource_id'];
        $this->capability->resource($resourceId);

        $release = is_array($event->payload['release'] ?? null) ? $event->payload['release'] : [];
        $releaseId = (int)($release['id'] ?? 0);
        if ($releaseId <= 0) throw new RuntimeException('GitHub release payload does not contain a valid release ID.');

        $record = \XF::finder('Warext\\GitHubSync:XfrmRelease')
            ->where('mapping_id', (int)$mapping->mapping_id)
            ->where('github_release_id', $releaseId)
            ->fetchOne();
        if (!$record)
        {
            $record = \XF::em()->create('Warext\\GitHubSync:XfrmRelease');
            $record->mapping_id = (int)$mapping->mapping_id;
            $record->repository_id = (int)$repository->repository_id;
            $record->github_release_id = $releaseId;
            $record->resource_id = $resourceId;
            $record->created_date = \XF::$time;
        }

        if (in_array($event->action, ['deleted', 'unpublished'], true))
        {
            return $this->deleteRelease($record, $target);
        }

        if ($record->exists() && (string)$record->release_hash === $payloadHash && (string)$record->status === 'synced')
        {
            return ['processed' => false, 'message' => 'XFRM release already synchronized.'];
        }

        $keyRef = trim((string)($target['xfrm_api_key_ref'] ?? ''));
        $userId = (int)($target['xfrm_api_user_id'] ?? 0);
        $bypass = !empty($target['xfrm_api_bypass']);
        $version = $this->versionString($release, $target);
        $downloadUrl = $this->downloads->resolve($release, $target);
        $title = trim((string)($release['name'] ?? '')) ?: $version;
        $message = trim((string)($release['body'] ?? ''));
        if ($message === '') $message = 'GitHub release: ' . (string)($release['html_url'] ?? '');

        $record->status = 'syncing';
        $record->last_error = '';
        $record->updated_date = \XF::$time;
        $record->save();

        try
        {
            if ((int)$record->resource_version_id === 0 && !empty($target['xfrm_create_version']))
            {
                $data = $this->api->createVersion($keyRef, $userId, $resourceId, $version, $downloadUrl, $bypass);
                $versionId = $this->api->idFromResponse($data, 'version');
                if ($versionId <= 0) throw new RuntimeException('XFRM API created a version but did not return its ID.');
                $record->resource_version_id = $versionId;
                $record->version_string = $version;
                $record->download_url = $downloadUrl;
                $record->updated_date = \XF::$time;
                $record->save();
            }

            if ((int)$record->resource_update_id === 0 && !empty($target['xfrm_create_update']))
            {
                $data = $this->api->createUpdate($keyRef, $userId, $resourceId, $title, $message, $bypass);
                $updateId = $this->api->idFromResponse($data, 'update');
                if ($updateId <= 0) throw new RuntimeException('XFRM API created an update but did not return its ID.');
                $record->resource_update_id = $updateId;
                $record->updated_date = \XF::$time;
                $record->save();
            }
            elseif ((int)$record->resource_update_id > 0 && !empty($target['xfrm_create_update']))
            {
                $this->api->updateUpdate($keyRef, $userId, (int)$record->resource_update_id, $title, $message, $bypass);
            }

            $warnings = [];
            if ((int)$record->resource_version_id > 0 && (string)$record->version_string !== '' && (string)$record->version_string !== $version)
            {
                $warnings[] = 'GitHub release tag/version changed after XFRM version creation; existing ResourceVersion was preserved.';
            }
            if ((int)$record->resource_version_id > 0 && (string)$record->download_url !== '' && (string)$record->download_url !== $downloadUrl)
            {
                $warnings[] = 'GitHub release download URL changed after XFRM version creation; existing ResourceVersion download URL was preserved.';
            }

            $record->release_hash = $payloadHash;
            if ((string)$record->version_string === '') $record->version_string = $version;
            if ((string)$record->download_url === '') $record->download_url = $downloadUrl;
            $record->status = $warnings ? 'attention' : 'synced';
            $record->last_error = implode(' ', $warnings);
            $record->updated_date = \XF::$time;
            $record->save();

            return [
                'processed' => true,
                'message' => $warnings ? implode(' ', $warnings) : 'XFRM resource synchronized.'
            ];
        }
        catch (\Throwable $e)
        {
            $record->status = 'failed';
            $record->last_error = mb_substr($e->getMessage(), 0, 1000);
            $record->updated_date = \XF::$time;
            $record->save();
            throw $e;
        }
    }

    private function deleteRelease($record, array $target): array
    {
        $policy = (string)($target['xfrm_delete_policy'] ?? 'ignore');
        if ($policy === 'ignore')
        {
            $record->status = 'remote_deleted';
            $record->updated_date = \XF::$time;
            $record->save();
            return ['processed' => true, 'message' => 'XFRM delete ignored by policy.'];
        }

        $keyRef = trim((string)($target['xfrm_api_key_ref'] ?? ''));
        $userId = (int)($target['xfrm_api_user_id'] ?? 0);
        $bypass = !empty($target['xfrm_api_bypass']);
        $hard = $policy === 'hard_delete';

        if ((int)$record->resource_update_id > 0)
        {
            $this->api->deleteUpdate($keyRef, $userId, (int)$record->resource_update_id, $hard, $bypass);
            $record->resource_update_id = 0;
            $record->save();
        }
        if ((int)$record->resource_version_id > 0)
        {
            $this->api->deleteVersion($keyRef, $userId, (int)$record->resource_version_id, $hard, $bypass);
            $record->resource_version_id = 0;
            $record->save();
        }
        $record->status = 'deleted';
        $record->last_error = '';
        $record->updated_date = \XF::$time;
        $record->save();
        return ['processed' => true, 'message' => 'XFRM resource version/update deleted.'];
    }

    private function versionString(array $release, array $target): string
    {
        $version = trim((string)($release['tag_name'] ?? ''));
        if (!empty($target['xfrm_strip_v']) && preg_match('/^v(?=\\d)/i', $version))
        {
            $version = substr($version, 1);
        }
        if ($version === '') throw new RuntimeException('GitHub release tag is empty; XFRM version cannot be created.');
        return mb_substr($version, 0, 100);
    }
}
