<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use XF\Mvc\ParameterBag;
use Warext\GitHubSync\Service\XFRM\Reconciler;
use Warext\GitHubSync\Service\XFRM\RetryService;
use Warext\GitHubSync\Service\XFRM\BulkRecoveryService;
use Warext\GitHubSync\Service\Admin\HealthAlertManager;

trait XfrmActions
{
    public function actionXfrm()
    {
        $status = trim($this->filter('status', 'str'));
        $finder = \XF::finder('Warext\\GitHubSync:XfrmRelease')->order('updated_date', 'DESC');
        if ($status !== '') $finder->where('status', $status);
        $records = $finder->limit(200)->fetch();
        return $this->view('Warext\\GitHubSync:GitHubSync\\Xfrm', 'wgh_xfrm', [
            'available' => class_exists('XFRM\\Entity\\ResourceItem'),
            'records' => $records,
            'recordCount' => \XF::finder('Warext\\GitHubSync:XfrmRelease')->total(),
            'failedCount' => \XF::finder('Warext\\GitHubSync:XfrmRelease')->where('status','failed')->total(),
            'attentionCount' => \XF::finder('Warext\\GitHubSync:XfrmRelease')->where('status','attention')->total(),
            'status' => $status
        ]);
    }

    public function actionXfrmBulk()
    {
        $this->assertPostOnly();
        $ids = $this->filter('xfrm_release_ids', 'array-uint');
        $operation = $this->filter('bulk_operation','str');
        if (!in_array($operation,['retry','reconcile'],true)) return $this->error('Select retry or reconcile.');
        if (!$ids) return $this->error('Select at least one XFRM synchronization record.');
        $result = (new BulkRecoveryService())->run($ids,$operation);
        (new HealthAlertManager())->evaluate();
        $message = sprintf('%d XFRM record(s) processed, %d failed.',(int)$result['processed'],(int)$result['failed']);
        if (!empty($result['errors'])) $message .= ' First error: ' . (string)$result['errors'][0];
        return $this->redirect($this->buildLink('github-sync/xfrm'),$message);
    }

    public function actionXfrmView(ParameterBag $params)
    {
        $id = (int)($params->xfrm_release_id ?: $this->filter('xfrm_release_id','uint'));
        $record = $this->assertRecordExists('Warext\\GitHubSync:XfrmRelease', $id);
        return $this->view('Warext\\GitHubSync:GitHubSync\\XfrmView', 'wgh_xfrm_view', [
            'record' => $record,
            'mapping' => \XF::em()->find('Warext\\GitHubSync:Mapping', (int)$record->mapping_id),
            'repository' => \XF::em()->find('Warext\\GitHubSync:Repository', (int)$record->repository_id),
            'history' => \XF::finder('Warext\\GitHubSync:XfrmHistory')->where('xfrm_release_id',$id)->order('created_date','DESC')->limit(100)->fetch()
        ]);
    }

    public function actionXfrmRetry(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id = (int)($params->xfrm_release_id ?: $this->filter('xfrm_release_id','uint'));
        $record = $this->assertRecordExists('Warext\\GitHubSync:XfrmRelease', $id);
        try
        {
            $result = (new RetryService())->retry($record);
            (new HealthAlertManager())->evaluate();
            return $this->redirect($this->buildLink('github-sync/xfrm-view', null, ['xfrm_release_id'=>$id]), (string)($result['message'] ?? 'XFRM retry completed.'));
        }
        catch (\Throwable $e) { return $this->error('XFRM retry failed: ' . $e->getMessage()); }
    }

    public function actionXfrmReconcile(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id = (int)($params->xfrm_release_id ?: $this->filter('xfrm_release_id','uint'));
        $record = $this->assertRecordExists('Warext\\GitHubSync:XfrmRelease', $id);
        try
        {
            $result = (new Reconciler())->reconcile($record);
            (new HealthAlertManager())->evaluate();
            $message = !empty($result['ok']) ? 'Remote XFRM objects verified.' : 'Reconcile completed with missing remote objects.';
            return $this->redirect($this->buildLink('github-sync/xfrm-view', null, ['xfrm_release_id'=>$id]), $message);
        }
        catch (\Throwable $e) { return $this->error('XFRM reconcile failed: ' . $e->getMessage()); }
    }

    public function actionXfrmHistoryRestore(ParameterBag $params)
    {
        $this->assertPostOnly();
        $historyId = (int)($params->xfrm_history_id ?: $this->filter('xfrm_history_id','uint'));
        $history = $this->assertRecordExists('Warext\\GitHubSync:XfrmHistory', $historyId);
        $record = $this->assertRecordExists('Warext\\GitHubSync:XfrmRelease', (int)$history->xfrm_release_id);
        $record->resource_version_id = (int)$history->resource_version_id;
        $record->resource_update_id = (int)$history->resource_update_id;
        $record->release_hash = (string)$history->release_hash;
        $record->status = 'attention';
        $record->remote_state = 'unknown';
        $record->last_error = 'Local tracking restored from history snapshot #' . $historyId . '; remote XFRM objects were not modified. Run reconcile.';
        $record->updated_date = \XF::$time;
        $record->save();
        (new \Warext\GitHubSync\Service\XFRM\HistoryLogger())->log($record, 'restore_tracking', 'attention', $record->last_error);
        (new HealthAlertManager())->evaluate();
        return $this->redirect($this->buildLink('github-sync/xfrm-view', null, ['xfrm_release_id'=>(int)$record->xfrm_release_id]), 'Tracking snapshot restored. Run reconcile before retrying.');
    }

    public function actionXfrmMappings()
    {
        $mappings = \XF::finder('Warext\\GitHubSync:Mapping')->where('event','release')->order('priority')->fetch();
        return $this->view('Warext\\GitHubSync:GitHubSync\\XfrmMappings','wgh_xfrm_mappings',[
            'mappings'=>$mappings,
            'available'=>class_exists('XFRM\\Entity\\ResourceItem')
        ]);
    }

    public function actionXfrmMappingEdit(ParameterBag $params)
    {
        $id = (int)($params->mapping_id ?: $this->filter('mapping_id','uint'));
        $mapping = $this->assertMappingExists($id);
        $target = array_replace([
            'xfrm_enabled'=>false,'xfrm_mode'=>'alongside','xfrm_resource_id'=>0,'xfrm_api_key_ref'=>'','xfrm_api_user_id'=>0,
            'xfrm_api_bypass'=>false,'xfrm_create_version'=>true,'xfrm_create_update'=>true,'xfrm_download_source'=>'asset',
            'xfrm_asset_pattern'=>'*.zip','xfrm_strip_v'=>true,'xfrm_delete_policy'=>'ignore'
        ], (array)$mapping->target_config);
        $resources = [];
        if (class_exists('XFRM\\Entity\\ResourceItem'))
        {
            $resources = \XF::finder('XFRM:ResourceItem')->order('title')->limit(500)->fetch();
        }
        return $this->view('Warext\\GitHubSync:GitHubSync\\XfrmMappingEdit','wgh_xfrm_mapping_edit',[
            'mapping'=>$mapping,'target'=>$target,'resources'=>$resources
        ]);
    }

    public function actionXfrmMappingSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id = (int)($params->mapping_id ?: $this->filter('mapping_id','uint'));
        $mapping = $this->assertMappingExists($id);
        $input = $this->filter([
            'xfrm_enabled'=>'bool','xfrm_mode'=>'str','xfrm_resource_id'=>'uint','xfrm_api_key_ref'=>'str','xfrm_api_user_id'=>'uint',
            'xfrm_api_bypass'=>'bool','xfrm_create_version'=>'bool','xfrm_create_update'=>'bool','xfrm_download_source'=>'str',
            'xfrm_asset_pattern'=>'str','xfrm_strip_v'=>'bool','xfrm_delete_policy'=>'str'
        ]);
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        $target['xfrm_enabled'] = (bool)$input['xfrm_enabled'];
        $target['xfrm_mode'] = in_array($input['xfrm_mode'],['alongside','only'],true) ? $input['xfrm_mode'] : 'alongside';
        $target['xfrm_resource_id'] = (int)$input['xfrm_resource_id'];
        $target['xfrm_api_key_ref'] = trim($input['xfrm_api_key_ref']);
        $target['xfrm_api_user_id'] = (int)$input['xfrm_api_user_id'];
        $target['xfrm_api_bypass'] = (bool)$input['xfrm_api_bypass'];
        $target['xfrm_create_version'] = (bool)$input['xfrm_create_version'];
        $target['xfrm_create_update'] = (bool)$input['xfrm_create_update'];
        $target['xfrm_download_source'] = in_array($input['xfrm_download_source'],['asset','zipball','release_page'],true) ? $input['xfrm_download_source'] : 'asset';
        $target['xfrm_asset_pattern'] = trim($input['xfrm_asset_pattern']) ?: '*.zip';
        $target['xfrm_strip_v'] = (bool)$input['xfrm_strip_v'];
        $target['xfrm_delete_policy'] = in_array($input['xfrm_delete_policy'],['ignore','soft_delete','hard_delete'],true) ? $input['xfrm_delete_policy'] : 'ignore';
        $mapping->target_config = $target;
        $mapping->updated_date = \XF::$time;
        $mapping->save();
        return $this->redirect($this->buildLink('github-sync/xfrm-mappings'), 'XFRM mapping updated.');
    }
}
