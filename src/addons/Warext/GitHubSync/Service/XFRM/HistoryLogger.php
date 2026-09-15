<?php

namespace Warext\GitHubSync\Service\XFRM;

final class HistoryLogger
{
    public function log($record, string $action, string $status, string $message = ''): void
    {
        if (!$record || !(int)$record->xfrm_release_id) return;
        $history = \XF::em()->create('Warext\\GitHubSync:XfrmHistory');
        $history->xfrm_release_id = (int)$record->xfrm_release_id;
        $history->mapping_id = (int)$record->mapping_id;
        $history->repository_id = (int)$record->repository_id;
        $history->action = mb_substr($action, 0, 50);
        $history->status = mb_substr($status, 0, 25);
        $history->message = mb_substr($message, 0, 1000);
        $history->resource_version_id = (int)$record->resource_version_id;
        $history->resource_update_id = (int)$record->resource_update_id;
        $history->release_hash = (string)$record->release_hash;
        $history->created_date = \XF::$time;
        $history->save();
    }
}
