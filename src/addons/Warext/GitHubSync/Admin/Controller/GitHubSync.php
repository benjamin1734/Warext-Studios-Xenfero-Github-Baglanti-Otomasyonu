<?php

namespace Warext\GitHubSync\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use Warext\GitHubSync\Admin\Controller\Traits\ConnectionRepositoryActions;
use Warext\GitHubSync\Admin\Controller\Traits\DeliveryDiagnosticActions;
use Warext\GitHubSync\Admin\Controller\Traits\MappingTemplateActions;
use Warext\GitHubSync\Admin\Controller\Traits\UserConflictActions;
use Warext\GitHubSync\Admin\Controller\Traits\WorkflowActions;
use Warext\GitHubSync\Admin\Controller\Traits\XfrmActions;
use Warext\GitHubSync\Admin\Controller\Traits\HealthActions;

class GitHubSync extends AbstractController
{
    use ConnectionRepositoryActions, MappingTemplateActions, UserConflictActions, DeliveryDiagnosticActions, WorkflowActions, XfrmActions, HealthActions;

    protected function preDispatchController($action, ParameterBag $params) { $this->assertAdminPermission('wghManage'); }

    public function actionIndex()
    {
        $stats=[
            'connections'=>\XF::finder('Warext\\GitHubSync:Connection')->total(),
            'repositories'=>\XF::finder('Warext\\GitHubSync:Repository')->total(),
            'mappings'=>\XF::finder('Warext\\GitHubSync:Mapping')->total(),
            'deliveries'=>\XF::finder('Warext\\GitHubSync:Delivery')->total(),
            'failed'=>\XF::finder('Warext\\GitHubSync:Delivery')->where('status','failed')->total(),
            'pending'=>\XF::finder('Warext\\GitHubSync:Delivery')->where('status',['received','processing'])->total(),
            'conflicts'=>\XF::finder('Warext\\GitHubSync:Conflict')->where('status','pending')->total(),
            'userMappings'=>\XF::finder('Warext\\GitHubSync:UserMapping')->where('active',1)->total(),
            'xfrmReleases'=>\XF::finder('Warext\\GitHubSync:XfrmRelease')->total(),
            'xfrmFailed'=>\XF::finder('Warext\\GitHubSync:XfrmRelease')->where('status','failed')->total(),
            'xfrmAttention'=>\XF::finder('Warext\\GitHubSync:XfrmRelease')->where('status','attention')->total()
        ];
        return $this->view('Warext\\GitHubSync:GitHubSync\\Dashboard','wgh_dashboard',[
            'stats'=>$stats,
            'recentDeliveries'=>\XF::finder('Warext\\GitHubSync:Delivery')->order('received_date','DESC')->limit(10)->fetch()
        ]);
    }

    protected function assertConnectionExists(int $id){ return $this->assertRecordExists('Warext\\GitHubSync:Connection',$id); }
    protected function assertMappingExists(int $id){ return $this->assertRecordExists('Warext\\GitHubSync:Mapping',$id); }
    protected function assertDeliveryExists(int $id){ return $this->assertRecordExists('Warext\\GitHubSync:Delivery',$id); }
    protected function assertTemplateExists(int $id){ return $this->assertRecordExists('Warext\\GitHubSync:Template',$id); }
    protected function assertUserMappingExists(int $id){ return $this->assertRecordExists('Warext\\GitHubSync:UserMapping',$id); }
    protected function assertConflictExists(int $id){ return $this->assertRecordExists('Warext\\GitHubSync:Conflict',$id); }

    protected function repositoryChoiceMap(): array
    {
        $map=[0=>'All repositories'];
        foreach(\XF::finder('Warext\\GitHubSync:Repository')->order('full_name')->fetch() as $repo) $map[(int)$repo->repository_id]=(string)$repo->full_name;
        return $map;
    }
    protected function mapLines(array $map): string { $o=[]; foreach($map as $k=>$v) if(trim((string)$k)!==''&&(int)$v>0)$o[]=trim((string)$k).'='.(int)$v; return implode("\n",$o); }
    protected function parseMapLines(string $value): array
    {
        $map=[]; foreach($this->lines($value) as $line){ if(!str_contains($line,'='))continue; [$k,$v]=array_map('trim',explode('=',$line,2)); if($k!==''&&(int)$v>0)$map[mb_strtolower($k)]=(int)$v; } return $map;
    }
    protected function stringMapLines(array $map): string
    {
        $out=[]; foreach($map as $k=>$v){ $k=trim((string)$k); $v=trim((string)$v); if($k!==''&&$v!=='')$out[]=$k.'='.$v; }
        return implode("\n",$out);
    }
    protected function parseStringMapLines(string $value): array
    {
        $map=[]; foreach($this->lines($value) as $line){ if(!str_contains($line,'='))continue; [$k,$v]=array_map('trim',explode('=',$line,2)); if($k!==''&&$v!=='')$map[$k]=$v; } return $map;
    }
    protected function reviewMapLines(array $map): string
    {
        $out=[]; foreach($map as $prefix=>$event){ $prefix=(int)$prefix; $event=strtoupper(trim((string)$event)); if($prefix>0&&$event!=='')$out[]=$prefix.'='.$event; } return implode("\n",$out);
    }
    protected function parseReviewMapLines(string $value): array
    {
        $map=[]; foreach($this->lines($value) as $line){ if(!str_contains($line,'='))continue; [$prefix,$event]=array_map('trim',explode('=',$line,2)); $prefix=(int)$prefix; $event=strtoupper($event); if($prefix>0&&in_array($event,['APPROVE','REQUEST_CHANGES','COMMENT'],true))$map[$prefix]=$event; } return $map;
    }
    protected function lines(string $value): array { return array_values(array_unique(array_filter(array_map('trim',preg_split('/\R+/',trim($value))?:[]),static fn($v)=>$v!==''))); }
}
