<?php

namespace Warext\GitHubSync\Service\Webhook;

final class SecretProvider
{
    public function getGlobalSecret(): string
    {
        $config = \XF::config('warextGitHubSync');
        if (!is_array($config))
        {
            return '';
        }

        return trim((string)($config['webhookSecret'] ?? ''));
    }
}
