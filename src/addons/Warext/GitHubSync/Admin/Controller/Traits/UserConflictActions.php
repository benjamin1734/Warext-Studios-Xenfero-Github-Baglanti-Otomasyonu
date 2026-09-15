<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use XF\Mvc\ParameterBag;
use Warext\GitHubSync\Service\Sync\ConflictResolver;

trait UserConflictActions
{
    public function actionUserMappings()
    {
        return $this->view('Warext\\GitHubSync:GitHubSync\\UserMappings', 'wgh_user_mappings', [
            'userMappings' => \XF::finder('Warext\\GitHubSync:UserMapping')->order('github_login')->fetch(),
            'repositories' => $this->repositoryChoiceMap(),
            'connections' => \XF::finder('Warext\\GitHubSync:Connection')->order('title')->fetch()
        ]);
    }

    public function actionUserMappingEdit(ParameterBag $params)
    {
        $id = (int)($params->user_mapping_id ?: $this->filter('user_mapping_id', 'uint'));
        $item = $id ? $this->assertUserMappingExists($id) : \XF::em()->create('Warext\\GitHubSync:UserMapping');
        if (!$item->exists()) $item->active = true;
        return $this->view('Warext\\GitHubSync:GitHubSync\\UserMappingEdit', 'wgh_user_mapping_edit', [
            'userMapping' => $item,
            'repositories' => \XF::finder('Warext\\GitHubSync:Repository')->order('full_name')->fetch(),
            'connections' => \XF::finder('Warext\\GitHubSync:Connection')->order('title')->fetch()
        ]);
    }

    public function actionUserMappingSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $input = $this->filter(['connection_id'=>'uint','repository_id'=>'uint','github_login'=>'str','xf_user_id'=>'uint','active'=>'bool']);
        $input['github_login'] = mb_strtolower(trim($input['github_login']));
        if ($input['github_login'] === '' || $input['xf_user_id'] <= 0) return $this->error('GitHub login and XenForo user ID are required.');
        $id = (int)($params->user_mapping_id ?: $this->filter('user_mapping_id', 'uint'));
        $item = $id ? $this->assertUserMappingExists($id) : \XF::em()->create('Warext\\GitHubSync:UserMapping');
        $isNew = !$item->exists();
        $item->bulkSet($input);
        if ($isNew) $item->created_date = \XF::$time;
        $item->updated_date = \XF::$time;
        $item->save();
        return $this->redirect($this->buildLink('github-sync/user-mappings'));
    }

    public function actionUserMappingDelete(ParameterBag $params)
    {
        $id = (int)($params->user_mapping_id ?: $this->filter('user_mapping_id', 'uint'));
        $item = $this->assertUserMappingExists($id);
        if ($this->isPost()) { $item->delete(); return $this->redirect($this->buildLink('github-sync/user-mappings')); }
        return $this->view('Warext\\GitHubSync:GitHubSync\\UserMappingDelete', 'wgh_user_mapping_delete', ['userMapping' => $item]);
    }

    public function actionConflicts()
    {
        $status = $this->filter('status', 'str') ?: 'pending';
        $finder = \XF::finder('Warext\\GitHubSync:Conflict')->order('created_date', 'DESC');
        if ($status !== 'all') $finder->where('status', $status);
        return $this->view('Warext\\GitHubSync:GitHubSync\\Conflicts', 'wgh_conflicts', ['conflicts'=>$finder->fetch(),'status'=>$status]);
    }

    public function actionConflict(ParameterBag $params)
    {
        $id = (int)($params->conflict_id ?: $this->filter('conflict_id', 'uint'));
        $conflict = $this->assertConflictExists($id);
        return $this->view('Warext\\GitHubSync:GitHubSync\\ConflictView', 'wgh_conflict_view', [
            'conflict' => $conflict,
            'payload' => json_encode((array)$conflict->action_payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
        ]);
    }

    public function actionConflictResolve(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id = (int)($params->conflict_id ?: $this->filter('conflict_id', 'uint'));
        try { (new ConflictResolver())->resolve($this->assertConflictExists($id), $this->filter('resolution', 'str')); }
        catch (\Throwable $e) { return $this->error('Conflict resolution failed: ' . $e->getMessage()); }
        return $this->redirect($this->buildLink('github-sync/conflicts'));
    }
}
