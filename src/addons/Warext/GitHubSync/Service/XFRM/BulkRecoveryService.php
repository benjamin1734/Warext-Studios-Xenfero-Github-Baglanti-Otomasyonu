<?php

namespace Warext\GitHubSync\Service\XFRM;

final class BulkRecoveryService
{
    public function run(array $ids, string $operation): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0))), 0, 50);
        $result = ['processed'=>0,'failed'=>0,'errors'=>[]];
        foreach ($ids as $id)
        {
            $record = \XF::em()->find('Warext\\GitHubSync:XfrmRelease', $id);
            if (!$record) { $result['failed']++; $result['errors'][] = '#'.$id.': record missing'; continue; }
            try
            {
                if ($operation === 'retry') (new RetryService())->retry($record);
                elseif ($operation === 'reconcile') (new Reconciler())->reconcile($record);
                else throw new \RuntimeException('Unsupported bulk recovery operation.');
                $result['processed']++;
            }
            catch (\Throwable $e)
            {
                $result['failed']++;
                $result['errors'][] = '#'.$id.': '.$e->getMessage();
            }
        }
        return $result;
    }
}
