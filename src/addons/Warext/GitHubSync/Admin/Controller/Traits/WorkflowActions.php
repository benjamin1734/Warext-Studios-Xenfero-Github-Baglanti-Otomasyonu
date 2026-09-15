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
        if(!$repository||!$repository->active)return $this->error('Repository was not found or is inactive.');
        if($workflowId===''||$ref==='')return $this->error('Workflow and ref are required.');
        $inputs=[];
        if($inputsJson!=='')try{$decoded=json_decode($inputsJson,true,512,JSON_THROW_ON_ERROR);if(!is_array($decoded))throw new \RuntimeException('Inputs JSON must be an object.');$inputs=$decoded;}catch(\Throwable $e){return $this->error('Invalid workflow inputs JSON: '.$e->getMessage());}
        try{[$api,$connection]=$this->workflowApi($repository);$api->dispatchWorkflow($connection,(string)$repository->owner_name,(string)$repository->repo_name,$workflowId,$ref,$inputs);}catch(\Throwable $e){return $this->error('Workflow dispatch failed: '.$e->getMessage());}
        return $this->redirect($this->buildLink('github-sync/actions',null,['repository_id'=>$repositoryId]),'Workflow dispatch accepted.');
    }

    public function actionWorkflowRunsRefresh()
    {
        $this->assertPostOnly();
        $repositoryId=$this->filter('repository_id','uint'); $repository=\XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId);
        if(!$repository||!$repository->active)return $this->error('Repository was not found or is inactive.');
        try
        {
            [$api,$connection]=$this->workflowApi($repository); $data=$api->listWorkflowRuns($connection,(string)$repository->owner_name,(string)$repository->repo_name,50);
            $tracker=new WorkflowRunTracker(); foreach((array)($data['workflow_runs']??[]) as $run) if(is_array($run))$tracker->import($repository,$run);
        }
        catch(\Throwable $e){return $this->error('Workflow history refresh failed: '.$e->getMessage());}
        return $this->redirect($this->buildLink('github-sync/actions',null,['repository_id'=>$repositoryId]),'Workflow run history refreshed.');
    }

    public function actionWorkflowRunControl()
    {
        $this->assertPostOnly();
        $repositoryId=$this->filter('repository_id','uint'); $runId=$this->filter('run_id','uint'); $operation=$this->filter('run_operation','str'); $debug=$this->filter('debug_logging','bool');
        $repository=\XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId);
        if(!$repository||!$repository->active)return $this->error('Repository was not found or is inactive.'); if($runId<=0)return $this->error('Workflow run ID is required.');
        try
        {
            [$api,$connection]=$this->workflowApi($repository);
            if($operation==='rerun_failed')$api->rerunFailedWorkflowJobs($connection,(string)$repository->owner_name,(string)$repository->repo_name,$runId,$debug);
            elseif($operation==='cancel')$api->cancelWorkflowRun($connection,(string)$repository->owner_name,(string)$repository->repo_name,$runId);
            else $api->rerunWorkflowRun($connection,(string)$repository->owner_name,(string)$repository->repo_name,$runId,$debug);
        }
        catch(\Throwable $e){return $this->error('Workflow run action failed: '.$e->getMessage());}
        return $this->redirect($this->buildLink('github-sync/actions',null,['repository_id'=>$repositoryId]),'Workflow run action accepted by GitHub.');
    }

    private function workflowApi($repository): array
    {
        $connection=\XF::em()->find('Warext\\GitHubSync:Connection',(int)$repository->connection_id); if(!$connection||!$connection->active)throw new \RuntimeException('GitHub connection is unavailable.');
        return [new ApiClient(new JwtFactory(),new CredentialProvider()),$connection];
    }
}
