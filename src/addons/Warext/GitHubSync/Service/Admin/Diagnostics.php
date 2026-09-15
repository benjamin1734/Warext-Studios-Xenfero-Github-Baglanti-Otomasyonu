<?php

namespace Warext\GitHubSync\Service\Admin;

use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;

final class Diagnostics
{
    public function run(): array
    {
        $checks = [];

        $checks[] = $this->check('PHP', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION);
        $checks[] = $this->check('OpenSSL', extension_loaded('openssl'), extension_loaded('openssl') ? OPENSSL_VERSION_TEXT : 'Missing');
        $checks[] = $this->check('JSON', extension_loaded('json'), extension_loaded('json') ? 'Available' : 'Missing');

        foreach ([
            'xf_wgh_connection',
            'xf_wgh_repository',
            'xf_wgh_mapping',
            'xf_wgh_template',
            'xf_wgh_sync_object',
            'xf_wgh_delivery',
            'xf_wgh_user_mapping',
            'xf_wgh_conflict',
            'xf_wgh_xfrm_release'
        ] as $table)
        {
            try
            {
                \XF::db()->fetchOne('SELECT 1 FROM ' . $table . ' LIMIT 1');
                $checks[] = $this->check('DB ' . $table, true, 'Available');
            }
            catch (\Throwable $e)
            {
                $checks[] = $this->check('DB ' . $table, false, $e->getMessage());
            }
        }

        $connections = \XF::finder('Warext\\GitHubSync:Connection')->order('title')->fetch();
        foreach ($connections as $connection)
        {
            $prefix = 'Connection: ' . $connection->title;
            try
            {
                $credentials = new CredentialProvider();
                $privateKey = $credentials->privateKey($connection);
                $privateKeyOk = str_contains($privateKey, 'BEGIN') && str_contains($privateKey, 'PRIVATE KEY');
                $checks[] = $this->check($prefix . ' private key', $privateKeyOk, $privateKeyOk ? 'Resolved' : 'Invalid PEM');
            }
            catch (\Throwable $e)
            {
                $checks[] = $this->check($prefix . ' private key', false, $e->getMessage());
            }

            try
            {
                $client = new ApiClient(new JwtFactory(), new CredentialProvider());
                $installation = $client->installation($connection);
                $login = (string)($installation['account']['login'] ?? '');
                $checks[] = $this->check($prefix . ' GitHub API', true, $login !== '' ? $login : 'Installation reachable');
            }
            catch (\Throwable $e)
            {
                $checks[] = $this->check($prefix . ' GitHub API', false, $e->getMessage());
            }
        }

        $xfrmAvailable = class_exists('XFRM\\Entity\\ResourceItem');
        $checks[] = $this->check('XFRM', $xfrmAvailable, $xfrmAvailable ? 'Loaded' : 'Not installed/loaded');
        if ($xfrmAvailable)
        {
            $config = \XF::config('warextGitHubSync');
            $keys = is_array($config) ? ($config['xfrmApiKeys'] ?? []) : [];
            foreach (\XF::finder('Warext\\GitHubSync:Mapping')->where('enabled',1)->fetch() as $mapping)
            {
                $target = is_array($mapping->target_config) ? $mapping->target_config : [];
                if (empty($target['xfrm_enabled'])) continue;
                $resourceId = (int)($target['xfrm_resource_id'] ?? 0);
                $resource = $resourceId > 0 ? \XF::em()->find('XFRM:ResourceItem', $resourceId) : null;
                $checks[] = $this->check('XFRM mapping #' . $mapping->mapping_id . ' resource', (bool)$resource, $resource ? ('Resource #' . $resourceId) : ('Missing resource #' . $resourceId));
                $ref = trim((string)($target['xfrm_api_key_ref'] ?? ''));
                $keyOk = $ref !== '' && is_array($keys) && trim((string)($keys[$ref] ?? '')) !== '';
                $checks[] = $this->check('XFRM mapping #' . $mapping->mapping_id . ' API key', $keyOk, $keyOk ? ('Resolved: ' . $ref) : ('Missing reference: ' . $ref));
            }
        }

        return $checks;
    }

    private function check(string $name, bool $ok, string $detail): array
    {
        return ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    }
}
