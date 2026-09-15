<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;

final class Reconciler
{
    private ApiClient $api;
    private HistoryLogger $history;

    public function __construct()
    {
        $this->api = new ApiClient(new CredentialProvider());
        $this->history = new HistoryLogger();
    }

    public function reconcile($record): array
    {
        $mapping = \XF::em()->find('Warext\\GitHubSync:Mapping', (int)$record->mapping_id);
        if (!$mapping) throw new RuntimeException('XFRM mapping no longer exists.');
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        $keyRef = trim((string)($target['xfrm_api_key_ref'] ?? ''));
        $userId = (int)($target['xfrm_api_user_id'] ?? 0);
        $bypass = !empty($target['xfrm_api_bypass']);

        $missing = [];
        $resourceExists = $this->exists(fn() => $this->api->getResource($keyRef, $userId, (int)$record->resource_id, $bypass));
        if (!$resourceExists) $missing[] = 'resource';
        if ($resourceExists && (int)$record->resource_version_id > 0 && !$this->exists(fn() => $this->api->getVersion($keyRef, $userId, (int)$record->resource_version_id, $bypass)))
            $missing[] = 'resource_version';
        if ($resourceExists && (int)$record->resource_update_id > 0 && !$this->exists(fn() => $this->api->getUpdate($keyRef, $userId, (int)$record->resource_update_id, $bypass)))
            $missing[] = 'resource_update';

        $record->last_reconcile_date = \XF::$time;
        $record->remote_state = $missing ? 'missing' : 'present';
        if ($missing)
        {
            $record->status = 'attention';
            $record->last_error = 'Remote XFRM objects missing: ' . implode(', ', $missing) . '.';
        }
        elseif ((string)$record->status === 'attention' && str_starts_with((string)$record->last_error, 'Remote XFRM objects missing:'))
        {
            $record->status = 'synced';
            $record->last_error = '';
        }
        $record->updated_date = \XF::$time;
        $record->save();
        $this->history->log($record, 'reconcile', $missing ? 'attention' : 'ok', $missing ? $record->last_error : 'Remote XFRM objects verified.');

        return ['ok' => !$missing, 'missing' => $missing];
    }

    private function exists(callable $callback): bool
    {
        try { $callback(); return true; }
        catch (ApiException $e)
        {
            if ($e->statusCode === 404) return false;
            throw $e;
        }
    }
}
