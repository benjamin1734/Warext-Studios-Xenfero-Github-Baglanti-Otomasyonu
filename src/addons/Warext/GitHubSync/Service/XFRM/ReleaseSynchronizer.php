<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;
use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

final class ReleaseSynchronizer
{
    private ApiClient $api;
    private Capability $capability;
    private DownloadResolver $downloads;
    private HistoryLogger $history;

    public function __construct()
    {
        $this->api = new ApiClient(new CredentialProvider());
        $this->capability = new Capability();
        $this->downloads = new DownloadResolver();
        $this->history = new HistoryLogger();
    }

    public function enabled(Mapping $mapping, WebhookEvent $event): bool
    {
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        return $event->event === 'release' && !empty($target['xfrm_enabled']) && (int)($target['xfrm_resource_id'] ?? 0) > 0;
    }
    public function mode(Mapping $mapping): string { $target=is_array($mapping->target_config)?$mapping->target_config:[]; return (string)($target['xfrm_mode'] ?? 'alongside'); }

    public function synchronize(Mapping $mapping, Repository $repository, WebhookEvent $event, string $payloadHash = ''): array
    {
        if (!$this->enabled($mapping, $event)) return ['processed'=>false,'message'=>''];
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        $resourceId = (int)$target['xfrm_resource_id'];
        $this->capability->resource($resourceId);
        $release = is_array($event->payload['release'] ?? null) ? $event->payload['release'] : [];
        $releaseId = (int)($release['id'] ?? 0);
        if ($releaseId <= 0) throw new RuntimeException('GitHub release payload does not contain a valid release ID.');
        $record = \XF::finder('Warext\\GitHubSync:XfrmRelease')->where('mapping_id',(int)$mapping->mapping_id)->where('github_release_id',$releaseId)->fetchOne();
        if (!$record)
        {
            $record = \XF::em()->create('Warext\\GitHubSync:XfrmRelease');
            $record->mapping_id=(int)$mapping->mapping_id; $record->repository_id=(int)$repository->repository_id; $record->github_release_id=$releaseId; $record->resource_id=$resourceId; $record->remote_state='unknown'; $record->created_date=\XF::$time; $record->save();
            $this->history->log($record,'created','pending','Created XFRM release tracking record.');
        }
        if (in_array($event->action,['deleted','unpublished'],true)) return $this->deleteRelease($record,$target);
        $semanticHash = $this->semanticHash($release,$target);
        if ((string)$record->release_hash === $semanticHash && (string)$record->status === 'synced') return ['processed'=>false,'message'=>'XFRM release already synchronized.'];

        $keyRef=trim((string)($target['xfrm_api_key_ref']??'')); $userId=(int)($target['xfrm_api_user_id']??0); $bypass=!empty($target['xfrm_api_bypass']);
        $version=$this->versionString($release,$target); $downloadUrl=$this->downloads->resolve($release,$target); $title=trim((string)($release['name']??''))?:$version; $message=trim((string)($release['body']??'')); if($message==='')$message='GitHub release: '.(string)($release['html_url']??'');
        $record->status='syncing'; $record->last_error=''; $record->last_attempt_date=\XF::$time; $record->updated_date=\XF::$time; $record->save();
        $this->history->log($record,'sync','started','XFRM release synchronization started.');
        try
        {
            if((int)$record->resource_version_id===0&&!empty($target['xfrm_create_version']))
            {
                $data=$this->api->createVersion($keyRef,$userId,$resourceId,$version,$downloadUrl,$bypass); $versionId=$this->api->idFromResponse($data,'version'); if($versionId<=0)throw new RuntimeException('XFRM API created a version but did not return its ID.');
                $record->resource_version_id=$versionId; $record->version_string=$version; $record->download_url=$downloadUrl; $record->updated_date=\XF::$time; $record->save(); $this->history->log($record,'create_version','ok','Created XFRM ResourceVersion.');
            }
            if((int)$record->resource_update_id===0&&!empty($target['xfrm_create_update']))
            {
                $data=$this->api->createUpdate($keyRef,$userId,$resourceId,$title,$message,$bypass); $updateId=$this->api->idFromResponse($data,'update'); if($updateId<=0)throw new RuntimeException('XFRM API created an update but did not return its ID.');
                $record->resource_update_id=$updateId; $record->updated_date=\XF::$time; $record->save(); $this->history->log($record,'create_update','ok','Created XFRM Resource Update.');
            }
            elseif((int)$record->resource_update_id>0&&!empty($target['xfrm_create_update'])) { $this->api->updateUpdate($keyRef,$userId,(int)$record->resource_update_id,$title,$message,$bypass); $this->history->log($record,'update_update','ok','Updated XFRM Resource Update from GitHub release notes.'); }
            $warnings=[];
            if((int)$record->resource_version_id>0&&(string)$record->version_string!==''&&(string)$record->version_string!==$version)$warnings[]='GitHub release tag/version changed after XFRM version creation; existing ResourceVersion was preserved.';
            if((int)$record->resource_version_id>0&&(string)$record->download_url!==''&&(string)$record->download_url!==$downloadUrl)$warnings[]='GitHub release download URL changed after XFRM version creation; existing ResourceVersion download URL was preserved.';
            $record->release_hash=$semanticHash; if((string)$record->version_string==='')$record->version_string=$version; if((string)$record->download_url==='')$record->download_url=$downloadUrl;
            $record->status=$warnings?'attention':'synced'; $record->remote_state='present'; $record->last_error=implode(' ',$warnings); $record->last_success_date=\XF::$time; $record->last_attempt_date=\XF::$time; $record->updated_date=\XF::$time; $record->save();
            $this->history->log($record,'sync',$warnings?'attention':'ok',$warnings?implode(' ',$warnings):'XFRM resource synchronized.');
            return ['processed'=>true,'message'=>$warnings?implode(' ',$warnings):'XFRM resource synchronized.'];
        }
        catch(\Throwable $e)
        {
            $record->status='failed'; $record->remote_state='unknown'; $record->last_error=mb_substr($e->getMessage(),0,1000); $record->last_attempt_date=\XF::$time; $record->updated_date=\XF::$time; $record->save(); $this->history->log($record,'sync','failed',$e->getMessage()); throw $e;
        }
    }

    private function deleteRelease($record,array $target):array
    {
        $policy=(string)($target['xfrm_delete_policy']??'ignore');
        if($policy==='ignore'){ $record->status='remote_deleted'; $record->remote_state='deleted'; $record->updated_date=\XF::$time; $record->save(); $this->history->log($record,'delete','ignored','GitHub release deletion ignored by mapping policy.'); return ['processed'=>true,'message'=>'XFRM delete ignored by policy.']; }
        $keyRef=trim((string)($target['xfrm_api_key_ref']??'')); $userId=(int)($target['xfrm_api_user_id']??0); $bypass=!empty($target['xfrm_api_bypass']); $hard=$policy==='hard_delete';
        try
        {
            if((int)$record->resource_update_id>0){$this->api->deleteUpdate($keyRef,$userId,(int)$record->resource_update_id,$hard,$bypass);$record->resource_update_id=0;$record->save();}
            if((int)$record->resource_version_id>0){$this->api->deleteVersion($keyRef,$userId,(int)$record->resource_version_id,$hard,$bypass);$record->resource_version_id=0;$record->save();}
            $record->status='deleted';$record->remote_state='deleted';$record->last_error='';$record->last_attempt_date=\XF::$time;$record->last_success_date=\XF::$time;$record->updated_date=\XF::$time;$record->save();$this->history->log($record,'delete','ok','XFRM ResourceVersion/ResourceUpdate deleted by policy.');return ['processed'=>true,'message'=>'XFRM resource version/update deleted.'];
        }catch(\Throwable $e){$record->status='failed';$record->last_error=mb_substr($e->getMessage(),0,1000);$record->last_attempt_date=\XF::$time;$record->updated_date=\XF::$time;$record->save();$this->history->log($record,'delete','failed',$e->getMessage());throw $e;}
    }

    private function semanticHash(array $release,array $target):string
    {
        $assets=[]; foreach((array)($release['assets']??[]) as $asset){if(!is_array($asset))continue;$assets[]=['id'=>(int)($asset['id']??0),'name'=>(string)($asset['name']??''),'url'=>(string)($asset['browser_download_url']??'')];}
        $data=['id'=>(int)($release['id']??0),'tag'=>(string)($release['tag_name']??''),'name'=>(string)($release['name']??''),'body'=>(string)($release['body']??''),'html_url'=>(string)($release['html_url']??''),'zipball_url'=>(string)($release['zipball_url']??''),'draft'=>!empty($release['draft']),'prerelease'=>!empty($release['prerelease']),'assets'=>$assets,'download_source'=>(string)($target['xfrm_download_source']??'asset'),'asset_pattern'=>(string)($target['xfrm_asset_pattern']??'*.zip'),'strip_v'=>!empty($target['xfrm_strip_v'])];
        return hash('sha256',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
    private function versionString(array $release,array $target):string
    {
        $version=trim((string)($release['tag_name']??'')); if(!empty($target['xfrm_strip_v'])&&preg_match('/^v(?=\\d)/i',$version))$version=substr($version,1); if($version==='')throw new RuntimeException('GitHub release tag is empty; XFRM version cannot be created.'); return mb_substr($version,0,100);
    }
}
