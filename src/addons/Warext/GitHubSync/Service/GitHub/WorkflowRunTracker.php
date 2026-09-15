<?php

namespace Warext\GitHubSync\Service\GitHub;

use Warext\GitHubSync\Dto\WebhookEvent;

final class WorkflowRunTracker
{
    public function capture($repository, WebhookEvent $event): void
    {
        if ($event->event !== 'workflow_run') return;
        $run = is_array($event->payload['workflow_run'] ?? null) ? $event->payload['workflow_run'] : [];
        $id = (int)($run['id'] ?? 0);
        if ($id <= 0) return;

        $entity = \XF::finder('Warext\\GitHubSync:WorkflowRun')->where('github_run_id', $id)->fetchOne();
        if (!$entity)
        {
            $entity = \XF::em()->create('Warext\\GitHubSync:WorkflowRun');
            $entity->github_run_id = $id;
            $entity->created_date = \XF::$time;
        }
        $entity->repository_id = (int)$repository->repository_id;
        $entity->github_workflow_id = (int)($run['workflow_id'] ?? 0);
        $entity->workflow_name = (string)($run['name'] ?? $run['display_title'] ?? '');
        $entity->run_number = (int)($run['run_number'] ?? 0);
        $entity->event = (string)($run['event'] ?? '');
        $entity->branch = (string)($run['head_branch'] ?? '');
        $entity->head_sha = (string)($run['head_sha'] ?? '');
        $entity->status = (string)($run['status'] ?? '');
        $entity->conclusion = (string)($run['conclusion'] ?? '');
        $entity->actor_login = (string)($run['actor']['login'] ?? $run['triggering_actor']['login'] ?? '');
        $entity->html_url = (string)($run['html_url'] ?? '');
        $entity->github_created_date = $this->date((string)($run['created_at'] ?? ''));
        $entity->github_updated_date = $this->date((string)($run['updated_at'] ?? ''));
        $entity->updated_date = \XF::$time;
        $entity->save();
    }

    public function import($repository, array $run): void
    {
        $event = new WebhookEvent('workflow-history-import', 'workflow_run', 'completed', (int)$repository->github_repository_id, (string)$repository->full_name, ['workflow_run' => $run]);
        $this->capture($repository, $event);
    }

    private function date(string $value): int
    {
        if ($value === '') return 0;
        $time = strtotime($value);
        return $time === false ? 0 : $time;
    }
}
