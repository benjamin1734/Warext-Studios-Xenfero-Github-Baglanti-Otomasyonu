<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;

final class CredentialProvider
{
    public function apiKey(string $ref): string
    {
        $ref = trim($ref);
        if ($ref === '')
        {
            throw new RuntimeException('XFRM local API key reference is missing.');
        }

        $config = \XF::config('warextGitHubSync');
        $keys = is_array($config) ? ($config['xfrmApiKeys'] ?? []) : [];
        $key = is_array($keys) ? trim((string)($keys[$ref] ?? '')) : '';
        if ($key === '')
        {
            throw new RuntimeException('XFRM local API key reference "' . $ref . '" could not be resolved.');
        }
        return $key;
    }
}
