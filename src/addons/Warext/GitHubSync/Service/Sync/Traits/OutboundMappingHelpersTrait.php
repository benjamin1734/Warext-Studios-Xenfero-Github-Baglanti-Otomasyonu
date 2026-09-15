<?php

namespace Warext\GitHubSync\Service\Sync\Traits;

use RuntimeException;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

trait OutboundMappingHelpersTrait
{
    private function mappingsForNode(int $nodeId): array
    {
        $out = [];
        foreach ($this->outboundMappings() as $mapping)
        {
            $source = $this->source($mapping);
            $mappedNode = (int)($source['xf_node_id'] ?? 0);
            if ($mappedNode > 0 && $mappedNode !== $nodeId) continue;
            if (!in_array((string)$source['outbound_object'], ['issue', 'pull_request'], true)) continue;
            $out[] = $mapping;
        }
        return $out;
    }

    private function outboundMappings(): array
    {
        $collection = \XF::finder('Warext\\GitHubSync:Mapping')
            ->where('enabled', 1)
            ->where('direction', ['xf_to_github', 'bidirectional'])
            ->where('repository_id', '>', 0)
            ->order('priority', 'ASC')
            ->fetch();
        $out = [];
        foreach ($collection as $mapping) $out[] = $mapping;
        return $out;
    }

    private function source(Mapping $mapping): array
    {
        $source = is_array($mapping->source_config) ? $mapping->source_config : [];
        return array_replace([
            'xf_node_id' => 0,
            'outbound_object' => 'issue',
            'sync_first_post' => true,
            'sync_replies' => true,
            'sync_thread_state' => true,
            'sync_labels' => false,
            'field_map' => [],
            'pr_head' => '',
            'pr_base' => '',
            'pr_draft' => false,
            'pr_maintainer_can_modify' => true,
            'sync_review_prefix' => false,
            'review_prefix_map' => [],
            'review_body' => ''
        ], $source);
    }

    private function repositoryAndConnection(Mapping $mapping): array
    {
        $repository = \XF::em()->find('Warext\\GitHubSync:Repository', (int)$mapping->repository_id);
        if (!$repository || !$repository->active)
        {
            throw new RuntimeException('Outbound mapping repository is missing or inactive.');
        }
        $connection = \XF::em()->find('Warext\\GitHubSync:Connection', (int)$repository->connection_id);
        if (!$connection || !$connection->active)
        {
            throw new RuntimeException('Outbound mapping GitHub connection is missing or inactive.');
        }
        return [$repository, $connection];
    }
}
