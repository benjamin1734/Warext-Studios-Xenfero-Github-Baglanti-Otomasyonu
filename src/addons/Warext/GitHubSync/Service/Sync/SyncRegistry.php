<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

final class SyncRegistry
{
    public function find(Mapping $mapping, string $githubType, string $githubId)
    {
        if ($githubId === '') return null;
        return \XF::finder('Warext\\GitHubSync:SyncObject')
            ->where('mapping_id', (int)$mapping->mapping_id)
            ->where('github_type', $githubType)
            ->where('github_id', $githubId)
            ->fetchOne();
    }

    public function findByXf(Mapping $mapping, string $xfType, int $xfId, ?string $githubType = null)
    {
        if ($xfId <= 0) return null;
        $finder = \XF::finder('Warext\\GitHubSync:SyncObject')
            ->where('mapping_id', (int)$mapping->mapping_id)
            ->where('xf_content_type', $xfType)
            ->where('xf_content_id', $xfId);
        if ($githubType !== null)
        {
            $finder->where('github_type', $githubType);
        }
        return $finder->fetchOne();
    }

    public function findInRepository(Repository $repository, string $githubType, string $githubId)
    {
        if ($githubId === '') return null;
        return \XF::finder('Warext\\GitHubSync:SyncObject')
            ->where('repository_id', (int)$repository->repository_id)
            ->where('github_type', $githubType)
            ->where('github_id', $githubId)
            ->order('last_sync_date', 'DESC')
            ->fetchOne();
    }

    public function findByXfInRepository(Repository $repository, string $xfType, int $xfId, ?string $githubType = null)
    {
        if ($xfId <= 0) return null;
        $finder = \XF::finder('Warext\\GitHubSync:SyncObject')
            ->where('repository_id', (int)$repository->repository_id)
            ->where('xf_content_type', $xfType)
            ->where('xf_content_id', $xfId);
        if ($githubType !== null) $finder->where('github_type', $githubType);
        return $finder->order('last_sync_date', 'DESC')->fetchOne();
    }

    public function register(
        Mapping $mapping,
        Repository $repository,
        string $githubType,
        string $githubId,
        string $xfType,
        int $xfId,
        string $githubHash,
        array $metadata = []
    )
    {
        $sync = $this->find($mapping, $githubType, $githubId);
        if (!$sync)
        {
            $sync = \XF::em()->create('Warext\\GitHubSync:SyncObject');
            $sync->mapping_id = (int)$mapping->mapping_id;
            $sync->repository_id = (int)$repository->repository_id;
            $sync->github_type = $githubType;
            $sync->github_id = $githubId;
        }
        $sync->xf_content_type = $xfType;
        $sync->xf_content_id = $xfId;
        $sync->sync_direction = (string)$mapping->direction;
        $sync->origin = 'github';
        $sync->last_github_hash = $githubHash;
        $sync->status = 'active';
        $sync->metadata = array_replace(is_array($sync->metadata) ? $sync->metadata : [], $metadata);
        $sync->last_sync_date = \XF::$time;
        $sync->save();
        return $sync;
    }

    public function registerOutbound(
        Mapping $mapping,
        Repository $repository,
        string $githubType,
        string $githubId,
        string $xfType,
        int $xfId,
        string $xfHash,
        array $metadata = []
    )
    {
        $sync = $this->find($mapping, $githubType, $githubId);
        if (!$sync)
        {
            $sync = $this->findByXf($mapping, $xfType, $xfId, $githubType);
        }
        if (!$sync)
        {
            $sync = \XF::em()->create('Warext\\GitHubSync:SyncObject');
            $sync->mapping_id = (int)$mapping->mapping_id;
            $sync->repository_id = (int)$repository->repository_id;
            $sync->github_type = $githubType;
            $sync->github_id = $githubId;
        }
        $sync->xf_content_type = $xfType;
        $sync->xf_content_id = $xfId;
        $sync->sync_direction = (string)$mapping->direction;
        $sync->origin = 'xenforo';
        $sync->last_xf_hash = $xfHash;
        $sync->status = 'active';
        $sync->metadata = array_replace(is_array($sync->metadata) ? $sync->metadata : [], $metadata);
        $sync->last_sync_date = \XF::$time;
        $sync->save();
        return $sync;
    }
}
