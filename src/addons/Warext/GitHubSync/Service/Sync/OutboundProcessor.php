<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;
use Warext\GitHubSync\Service\Sync\Traits\OutboundCommentActionsTrait;
use Warext\GitHubSync\Service\Sync\Traits\OutboundMappingHelpersTrait;
use Warext\GitHubSync\Service\Sync\Traits\OutboundPrimaryActionsTrait;

final class OutboundProcessor
{
    use OutboundPrimaryActionsTrait, OutboundCommentActionsTrait, OutboundMappingHelpersTrait;

    private ApiClient $api;
    private SyncRegistry $registry;
    private LabelPrefixMapper $labelPrefixMapper;
    private FieldMapper $fieldMapper;

    public function __construct()
    {
        $this->registry = new SyncRegistry();
        $this->api = new ApiClient(new JwtFactory(), new CredentialProvider());
        $this->labelPrefixMapper = new LabelPrefixMapper();
        $this->fieldMapper = new FieldMapper();
    }

    public function process(string $contentType, int $contentId, string $operation, array $extra = []): void
    {
        if (SyncGuard::active()) return;

        SyncGuard::run(function() use ($contentType, $contentId, $operation, $extra)
        {
            if ($contentType === 'thread')
            {
                $operation === 'delete'
                    ? $this->deleteThread($contentId, $extra)
                    : $this->syncThread($contentId);
                return;
            }
            if ($contentType === 'post')
            {
                $operation === 'delete'
                    ? $this->deletePost($contentId, $extra)
                    : $this->syncPost($contentId);
            }
        });
    }
}
