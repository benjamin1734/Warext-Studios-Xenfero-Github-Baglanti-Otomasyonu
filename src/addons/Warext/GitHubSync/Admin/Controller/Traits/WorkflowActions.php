<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;
use Warext\GitHubSync\Service\GitHub\WorkflowRunTracker;

trait WorkflowActions
{
    public function actionActions()
    {
        $repositoryId = $this->filter('repository_id', 'uint');
        $repositories = \XF::finder('Warext\\GitHubSync:Repository')->where('active', 1)->order('full_name')->fetch();
        $selected = $repositoryId ? \XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId) : null;
        $workflows = []; $error = '';
        if ($selected && $selected->active)
        {
            try
            {
                [$api,$connection] = $this->workflowApi($selected);
                $data = $api->listWorkflows($connection,(string)$selected->owner_name,(string)$selected->repo_name);
                $workflows = is_array($data['workflows'] ?? null) ? $data['workflows'] : [];
            }
            catch (\Throwable $e) { $error = $e->getMessage(); }
        }
        $runs = $repositoryId ? \XF::finder('Warext\\GitHubSync:WorkflowRun')->where('repository_id',$repositoryId)->order('updated_date','DESC')->limit(50)->fetch() : [];
        return $this->view('Warext\\GitHubSync:GitHubSync\\Actions', 'wgh_actions', [
            'repositories'=>$repositories,'repositoryId'=>$repositoryId,'selectedRepository'=>$selected,'workflows'=>$workflows,'runs'=>$runs,'error'=>$error
        ]);
    }

    public function actionWorkflowDispatch()
    {
        $this->assertPostOnly();
        $repositoryId=$this->filter('repository_id','uint'); $workflowId=trim($this->filter('workflow_id','str')); $ref=trim($this->filter('ref','str')); $inputsJson=trim($this->filter('inputs_json','str'));
        $repository=\XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId);
        if(!$repository||!$repository->active)return $this->error(\XF::phrase('wgh_msg_repository_not_found_inactive'));
        if($workflowId===''||$ref==='')return $this->error(\XF::phrase('wgh_msg_workflow_ref_required'));
        $inputs=[];
        if($inputsJson!=='')try{$decoded=json_decode($inputsJson,true,512,JSON_THROW_ON_ERROR);if(!is_array($decoded))throw new \RuntimeException((string)\XF::phrase('wgh_msg_inputs_json_object'));$inputs=$decoded;}catch(\Throwable $e){return $this->error(\XF::phrase('wgh_msg_invalid_workflow_inputs', ['error' => $e->getMessage()]));}
        try{[$api,$connection]=$this->workflowApi($repository);$api->dispatchWorkflow($connection,(string)$repository->owner_name,(string)$repository->repo_name,$workflowId,$ref,$inputs);}catch(\Throwable $e){return $this->error(\XF::phrase('wgh_msg_workflow_dispatch_failed', ['error' => $e->getMessage()]));}
        return $this->redirect($this->buildLink('github-sync/actions',null,['repository_id'=>$repositoryId]),\XF::phrase('wgh_msg_workflow_dispatch_accepted'));
    }

    public function actionWorkflowRunsRefresh()
    {
        $this->assertPostOnly();
        $repositoryId=$this->filter('repository_id','uint'); $repository=\XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId);
        if(!$repository||!$repository->active)return $this->error(\XF::phrase('wgh_msg_repository_not_found_inactive'));
        try
        {
            [$api,$connection]=$this->workflowApi($repository); $data=$api->listWorkflowRuns($connection,(string)$repository->owner_name,(string)$repository->repo_name,50);
            $tracker=new WorkflowRunTracker(); foreach((array)($data['workflow_runs']??[]) as $run) if(is_array($run))$tracker->import($repository,$run);
        }
        catch(\Throwable $e){return $this->error(\XF::phrase('wgh_msg_workflow_history_refresh_failed', ['error' => $e->getMessage()]));}
        return $this->redirect($this->buildLink('github-sync/actions',null,['repository_id'=>$repositoryId]),\XF::phrase('wgh_msg_workflow_history_refreshed'));
    }

    public function actionWorkflowRunControl()
    {
        $this->assertPostOnly();
        $repositoryId=$this->filter('repository_id','uint'); $runId=$this->filter('run_id','uint'); $operation=$this->filter('run_operation','str'); $debug=$this->filter('debug_logging','bool');
        $repository=\XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId);
        if(!$repository||!$repository->active)return $this->error(\XF::phrase('wgh_msg_repository_not_found_inactive')); if($runId<=0)return $this->error(\XF::phrase('wgh_msg_workflow_run_id_required'));
        try
        {
            [$api,$connection]=$this->workflowApi($repository);
            if($operation==='rerun_failed')$api->rerunFailedWorkflowJobs($connection,(string)$repository->owner_name,(string)$repository->repo_name,$runId,$debug);
            elseif($operation==='cancel')$api->cancelWorkflowRun($connection,(string)$repository->owner_name,(string)$repository->repo_name,$runId);
            else $api->rerunWorkflowRun($connection,(string)$repository->owner_name,(string)$repository->repo_name,$runId,$debug);
        }
        catch(\Throwable $e){return $this->error(\XF::phrase('wgh_msg_workflow_run_action_failed', ['error' => $e->getMessage()]));}
        return $this->redirect($this->buildLink('github-sync/actions',null,['repository_id'=>$repositoryId]),\XF::phrase('wgh_msg_workflow_run_action_accepted'));
    }

    private function workflowApi($repository): array
    {
        $connection=\XF::em()->find('Warext\\GitHubSync:Connection',(int)$repository->connection_id); if(!$connection||!$connection->active)throw new \RuntimeException((string)\XF::phrase('wgh_msg_github_connection_unavailable'));
        return [new ApiClient(new JwtFactory(),new CredentialProvider()),$connection];
    }
}
