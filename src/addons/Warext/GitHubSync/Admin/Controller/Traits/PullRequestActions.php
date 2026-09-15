<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;

trait PullRequestActions
{
    public function actionPullRequests()
    {
        $repositoryId = $this->filter('repository_id', 'uint');
        $number = $this->filter('pull_request_number', 'uint');
        $repositories = \XF::finder('Warext\\GitHubSync:Repository')->where('active',1)->order('full_name')->fetch();
        $repository = $repositoryId ? \XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId) : null;
        $pullRequest = null; $requested = ['users'=>[],'teams'=>[]]; $error = '';
        if ($repository && $number > 0)
        {
            try
            {
                [$api,$connection] = $this->githubApiForRepository($repository);
                $pullRequest = $api->getPullRequest($connection,(string)$repository->owner_name,(string)$repository->repo_name,$number);
                $requested = $api->getRequestedReviewers($connection,(string)$repository->owner_name,(string)$repository->repo_name,$number);
            }
            catch (\Throwable $e) { $error = $e->getMessage(); }
        }
        return $this->view('Warext\\GitHubSync:GitHubSync\\PullRequests','wgh_pull_requests',[
            'repositories'=>$repositories,'repositoryId'=>$repositoryId,'repository'=>$repository,'number'=>$number,
            'pullRequest'=>$pullRequest,'requested'=>$requested,'error'=>$error
        ]);
    }

    public function actionPullRequestReviewers()
    {
        $this->assertPostOnly();
        $repositoryId = $this->filter('repository_id','uint');
        $number = $this->filter('pull_request_number','uint');
        $mode = $this->filter('reviewer_action','str');
        $reviewers = $this->csvValues($this->filter('reviewers','str'));
        $teams = $this->csvValues($this->filter('team_reviewers','str'));
        $repository = \XF::em()->find('Warext\\GitHubSync:Repository',$repositoryId);
        if (!$repository || !$repository->active) return $this->error('Repository was not found or is inactive.');
        if ($number <= 0) return $this->error('Pull request number is required.');
        try
        {
            [$api,$connection] = $this->githubApiForRepository($repository);
            if ($mode === 'remove') $api->removeRequestedReviewers($connection,(string)$repository->owner_name,(string)$repository->repo_name,$number,$reviewers,$teams);
            else $api->requestReviewers($connection,(string)$repository->owner_name,(string)$repository->repo_name,$number,$reviewers,$teams);
        }
        catch (\Throwable $e) { return $this->error('Pull request reviewer action failed: '.$e->getMessage()); }
        return $this->redirect($this->buildLink('github-sync/pull-requests',null,['repository_id'=>$repositoryId,'pull_request_number'=>$number]), 'Reviewer requests updated.');
    }

    private function githubApiForRepository($repository): array
    {
        $connection = \XF::em()->find('Warext\\GitHubSync:Connection',(int)$repository->connection_id);
        if (!$connection || !$connection->active) throw new \RuntimeException('GitHub connection is unavailable.');
        return [new ApiClient(new JwtFactory(),new CredentialProvider()),$connection];
    }

    private function csvValues(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim',preg_split('/[,\r\n]+/',$value) ?: []),static fn($v)=>$v!=='')));
    }
}
