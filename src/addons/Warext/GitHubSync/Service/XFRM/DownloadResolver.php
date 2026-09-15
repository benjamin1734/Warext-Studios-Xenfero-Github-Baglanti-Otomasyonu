<?php

namespace Warext\GitHubSync\Service\XFRM;

final class DownloadResolver
{
    public function resolve(array $release, array $config): string
    {
        $mode = (string)($config['xfrm_download_source'] ?? 'asset');
        if ($mode === 'release_page') return (string)($release['html_url'] ?? '');
        if ($mode === 'zipball') return (string)($release['zipball_url'] ?? $release['html_url'] ?? '');

        $pattern = trim((string)($config['xfrm_asset_pattern'] ?? '*.zip'));
        $assets = is_array($release['assets'] ?? null) ? $release['assets'] : [];
        foreach ($assets as $asset)
        {
            if (!is_array($asset)) continue;
            $name = (string)($asset['name'] ?? '');
            if ($name !== '' && $this->matches($pattern, $name))
            {
                return (string)($asset['browser_download_url'] ?? '');
            }
        }
        foreach ($assets as $asset)
        {
            if (!is_array($asset)) continue;
            $name = (string)($asset['name'] ?? '');
            if ($name !== '' && str_ends_with(mb_strtolower($name), '.zip'))
            {
                return (string)($asset['browser_download_url'] ?? '');
            }
        }
        return (string)($release['zipball_url'] ?? $release['html_url'] ?? '');
    }

    private function matches(string $pattern, string $value): bool
    {
        if ($pattern === '' || $pattern === '*') return true;
        $quoted = preg_quote($pattern, '~');
        $regex = '~^' . str_replace(['\\*', '\\?'], ['.*', '.'], $quoted) . '$~iu';
        return (bool)preg_match($regex, $value);
    }
}
