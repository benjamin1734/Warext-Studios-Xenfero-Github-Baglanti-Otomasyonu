<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

trait XfrmActions
{
    public function actionXfrm()
    {
        $available = class_exists('XFRM\\Entity\\ResourceItem');
        $records = \XF::finder('Warext\\GitHubSync:XfrmRelease')
            ->order('updated_date', 'DESC')
            ->limit(100)
            ->fetch();
        return $this->view('Warext\\GitHubSync:GitHubSync\\Xfrm', 'wgh_xfrm', [
            'available' => $available,
            'records' => $records,
            'recordCount' => \XF::finder('Warext\\GitHubSync:XfrmRelease')->total()
        ]);
    }
}
