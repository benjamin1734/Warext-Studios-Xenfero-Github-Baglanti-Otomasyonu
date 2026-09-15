<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use XF\Mvc\ParameterBag;
use Warext\GitHubSync\Service\Admin\Diagnostics;

trait DeliveryDiagnosticActions
{
    public function actionDeliveries()
    {
        $page = $this->filterPage(); $perPage = 50; $status = $this->filter('status','str');
        $finder = \XF::finder('Warext\\GitHubSync:Delivery')->order('received_date','DESC');
        if ($status !== '') $finder->where('status',$status);
        $total = $finder->total();
        return $this->view('Warext\\GitHubSync:GitHubSync\\Deliveries','wgh_deliveries',[
            'deliveries'=>$finder->limitByPage($page,$perPage)->fetch(),'page'=>$page,'perPage'=>$perPage,'total'=>$total,'status'=>$status
        ]);
    }

    public function actionDelivery(ParameterBag $params)
    {
        $id=(int)($params->delivery_id ?: $this->filter('delivery_id','uint')); $delivery=$this->assertDeliveryExists($id);
        $payload=json_decode((string)$delivery->payload,true);
        return $this->view('Warext\\GitHubSync:GitHubSync\\DeliveryView','wgh_delivery_view',[
            'delivery'=>$delivery,'prettyPayload'=>json_encode(is_array($payload)?$payload:[],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function actionDeliveryRetry(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id=(int)($params->delivery_id ?: $this->filter('delivery_id','uint')); $d=$this->assertDeliveryExists($id);
        $d->status='received'; $d->attempt_count=0; $d->last_error=''; $d->processed_date=0; $d->next_attempt_date=0; $d->grouped_into_delivery_id=0; $d->save();
        \XF::app()->jobManager()->enqueueUnique('warextGitHubSyncDeliveryRetry'.$d->delivery_id,'Warext\\GitHubSync:ProcessDelivery',['delivery_id'=>(int)$d->delivery_id]);
        return $this->redirect($this->buildLink('github-sync/delivery',null,['delivery_id'=>(int)$d->delivery_id]));
    }

    public function actionDiagnostics()
    {
        return $this->view('Warext\\GitHubSync:GitHubSync\\Diagnostics','wgh_diagnostics',['checks'=>(new Diagnostics())->run()]);
    }
}
