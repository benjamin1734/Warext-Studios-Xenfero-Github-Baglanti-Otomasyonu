<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use XF\Mvc\ParameterBag;
use Warext\GitHubSync\Service\Admin\HealthAlertManager;
use Warext\GitHubSync\Service\Admin\HealthMonitor;

trait HealthActions
{
    public function actionHealth()
    {
        $snapshot = (new HealthAlertManager())->evaluate((new HealthMonitor())->snapshot());
        return $this->view('Warext\\GitHubSync:GitHubSync\\Health','wgh_health',[
            'health'=>$snapshot,
            'alerts'=>\XF::finder('Warext\\GitHubSync:HealthAlert')->order('last_seen_date','DESC')->limit(100)->fetch()
        ]);
    }

    public function actionHealthEvaluate()
    {
        $this->assertPostOnly();
        (new HealthAlertManager())->evaluate();
        return $this->redirect($this->buildLink('github-sync/health'),'Health state evaluated.');
    }

    public function actionHealthAlertResolve(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id=(int)($params->health_alert_id ?: $this->filter('health_alert_id','uint'));
        $alert=$this->assertRecordExists('Warext\\GitHubSync:HealthAlert',$id);
        $alert->status='resolved'; $alert->resolved_date=\XF::$time; $alert->save();
        return $this->redirect($this->buildLink('github-sync/health'),'Health alert resolved.');
    }
}
