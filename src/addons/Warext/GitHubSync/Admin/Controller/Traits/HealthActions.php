<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use Warext\GitHubSync\Service\Admin\HealthMonitor;

trait HealthActions
{
    public function actionHealth()
    {
        return $this->view('Warext\\GitHubSync:GitHubSync\\Health','wgh_health',[
            'health'=>(new HealthMonitor())->snapshot()
        ]);
    }
}
