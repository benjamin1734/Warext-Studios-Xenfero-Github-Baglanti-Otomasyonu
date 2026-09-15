<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;

trait WorkflowActions
{
    public function actionActions()
    {
        $repositoryId = $this->filter('repository_id', 'uint');
        $repositories = \XF::finder('Warext\\GitHubSync:Repository')->where('active', 1)->order('full_name')->fetch();
        $selected = null;
        $workflows = [];
        $error = '';

        if ($repositoryId > 0)
        {
            $selected = \XF::em()->find('Warext\\GitHubSync:Repository', $repositoryId);
            if ($selected && $selected->active)
            {
                try
                {
                    $connection = \XF::em()->find('Warext\\GitHubSync:Connection', (int)$selected->connection_id);
                    if (!$connection || !$connection->active) throw new \RuntimeException('GitHub connection is unavailable.');
                    $data = (new ApiClient(new JwtFactory(), new CredentialProvider()))->listWorkflows(
                        $connection,
                        (string)$selected->owner_name,
                        (string)$selected->repo_name
                    );
                    $workflows = is_array($data['workflows'] ?? null) ? $data['workflows'] : [];
                }
                catch (\Throwable $e)
                {
                    $error = $e->getMessage();
                }
            }
        }

        return $this->view('Warext\\GitHubSync:GitHubSync\\Actions', 'wgh_actions', [
            'repositories' => $repositories,
            'repositoryId' => $repositoryId,
            'selectedRepository' => $selected,
            'workflows' => $workflows,
            'error' => $error
        ]);
    }

    public function actionWorkflowDispatch()
    {
        $this->assertPostOnly();
        $repositoryId = $this->filter('repository_id', 'uint');
        $workflowId = trim($this->filter('workflow_id', 'str'));
        $ref = trim($this->filter('ref', 'str'));
        $inputsJson = trim($this->filter('inputs_json', 'str'));

        $repository = \XF::em()->find('Warext\\GitHubSync:Repository', $repositoryId);
        if (!$repository || !$repository->active) return $this->error('Repository was not found or is inactive.');
        if ($workflowId === '' || $ref === '') return $this->error('Workflow and ref are required.');

        $inputs = [];
        if ($inputsJson !== '')
        {
            try
            {
                $decoded = json_decode($inputsJson, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($decoded)) throw new \RuntimeException('Inputs JSON must be an object.');
                $inputs = $decoded;
            }
            catch (\Throwable $e)
            {
                return $this->error('Invalid workflow inputs JSON: ' . $e->getMessage());
            }
        }

        $connection = \XF::em()->find('Warext\\GitHubSync:Connection', (int)$repository->connection_id);
        if (!$connection || !$connection->active) return $this->error('GitHub connection is unavailable.');

        try
        {
            (new ApiClient(new JwtFactory(), new CredentialProvider()))->dispatchWorkflow(
                $connection,
                (string)$repository->owner_name,
                (string)$repository->repo_name,
                $workflowId,
                $ref,
                $inputs
            );
        }
        catch (\Throwable $e)
        {
            return $this->error('Workflow dispatch failed: ' . $e->getMessage());
        }

        return $this->redirect($this->buildLink('github-sync/actions', null, ['repository_id' => $repositoryId]));
    }
}
