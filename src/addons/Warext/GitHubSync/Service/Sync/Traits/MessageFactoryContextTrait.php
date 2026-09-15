<?php

namespace Warext\GitHubSync\Service\Sync\Traits;

use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Entity\Repository;

trait MessageFactoryContextTrait
{
    private function variables(Repository $repository, WebhookEvent $event): array
    {
        $p = $event->payload;
        $release = (array)($p['release'] ?? []);
        $sender = (array)($p['sender'] ?? []);
        $headCommit = (array)($p['head_commit'] ?? []);
        $commits = (array)($p['commits'] ?? []);
        $issue = (array)($p['issue'] ?? []);
        $comment = (array)($p['comment'] ?? []);
        $pr = (array)($p['pull_request'] ?? []);
        $review = (array)($p['review'] ?? []);
        $workflowRun = (array)($p['workflow_run'] ?? []);
        $workflowJob = (array)($p['workflow_job'] ?? []);

        $commitLines = [];
        foreach (array_slice($commits, 0, 50) as $commit)
        {
            if (!is_array($commit)) continue;
            $sha = substr((string)($commit['id'] ?? ''), 0, 7);
            $message = trim((string)($commit['message'] ?? ''));
            $url = (string)($commit['url'] ?? '');
            $commitLines[] = '• [URL="' . $url . '"]' . $sha . '[/URL] ' . $message;
        }

        $labels = $this->labelNames((array)($issue['labels'] ?? $pr['labels'] ?? []));
        $issueIsPr = !empty($issue['pull_request']);

        return [
            'repo' => [
                'id' => (int)$repository->github_repository_id,
                'name' => (string)$repository->repo_name,
                'full_name' => (string)$repository->full_name,
                'url' => (string)$repository->html_url,
                'default_branch' => (string)$repository->default_branch
            ],
            'event' => $event->event,
            'action' => $event->action,
            'sender' => [
                'login' => (string)($sender['login'] ?? ''),
                'url' => (string)($sender['html_url'] ?? '')
            ],
            'release' => [
                'id' => (string)($release['id'] ?? ''),
                'tag' => (string)($release['tag_name'] ?? ''),
                'name' => (string)($release['name'] ?? ''),
                'body' => (string)($release['body'] ?? ''),
                'url' => (string)($release['html_url'] ?? ''),
                'prerelease' => !empty($release['prerelease']) ? '1' : '0',
                'draft' => !empty($release['draft']) ? '1' : '0',
                'published_at' => (string)($release['published_at'] ?? '')
            ],
            'push' => [
                'ref' => (string)($p['ref'] ?? ''),
                'branch' => $this->branchFromRef((string)($p['ref'] ?? '')),
                'compare_url' => (string)($p['compare'] ?? ''),
                'commit_count' => count($commits),
                'commits' => implode("\n", $commitLines),
                'head_message' => (string)($headCommit['message'] ?? '')
            ],
            'issue' => [
                'id' => (string)($issue['id'] ?? ''),
                'number' => (string)($issue['number'] ?? ''),
                'title' => (string)($issue['title'] ?? ''),
                'body' => (string)($issue['body'] ?? ''),
                'state' => (string)($issue['state'] ?? ''),
                'state_reason' => (string)($issue['state_reason'] ?? ''),
                'url' => (string)($issue['html_url'] ?? ''),
                'labels' => implode(', ', $labels),
                'is_pull_request' => $issueIsPr ? '1' : '0',
                'user' => (string)($issue['user']['login'] ?? '')
            ],
            'comment' => [
                'id' => (string)($comment['id'] ?? ''),
                'body' => (string)($comment['body'] ?? ''),
                'url' => (string)($comment['html_url'] ?? ''),
                'user' => (string)($comment['user']['login'] ?? '')
            ],
            'pr' => [
                'id' => (string)($pr['id'] ?? ''),
                'number' => (string)($pr['number'] ?? ''),
                'title' => (string)($pr['title'] ?? ''),
                'body' => (string)($pr['body'] ?? ''),
                'state' => (string)($pr['state'] ?? ''),
                'url' => (string)($pr['html_url'] ?? ''),
                'draft' => !empty($pr['draft']) ? '1' : '0',
                'merged' => !empty($pr['merged']) ? '1' : '0',
                'base' => (string)($pr['base']['ref'] ?? ''),
                'head' => (string)($pr['head']['ref'] ?? ''),
                'user' => (string)($pr['user']['login'] ?? ''),
                'labels' => implode(', ', $labels)
            ],
            'review' => [
                'id' => (string)($review['id'] ?? ''),
                'body' => (string)($review['body'] ?? ''),
                'state' => (string)($review['state'] ?? ''),
                'url' => (string)($review['html_url'] ?? ''),
                'user' => (string)($review['user']['login'] ?? '')
            ],
            'workflow' => [
                'id' => (string)($workflowRun['id'] ?? $workflowJob['run_id'] ?? ''),
                'name' => (string)($workflowRun['name'] ?? $workflowJob['name'] ?? ''),
                'run_number' => (string)($workflowRun['run_number'] ?? ''),
                'run_attempt' => (string)($workflowRun['run_attempt'] ?? $workflowJob['run_attempt'] ?? ''),
                'status' => (string)($workflowRun['status'] ?? $workflowJob['status'] ?? ''),
                'conclusion' => (string)($workflowRun['conclusion'] ?? $workflowJob['conclusion'] ?? ''),
                'event' => (string)($workflowRun['event'] ?? ''),
                'branch' => (string)($workflowRun['head_branch'] ?? $workflowJob['head_branch'] ?? ''),
                'sha' => (string)($workflowRun['head_sha'] ?? $workflowJob['head_sha'] ?? ''),
                'url' => (string)($workflowRun['html_url'] ?? $workflowJob['html_url'] ?? ''),
                'actor' => (string)($workflowRun['actor']['login'] ?? $workflowJob['runner_name'] ?? '')
            ],
            'job' => [
                'id' => (string)($workflowJob['id'] ?? ''),
                'run_id' => (string)($workflowJob['run_id'] ?? ''),
                'name' => (string)($workflowJob['name'] ?? ''),
                'status' => (string)($workflowJob['status'] ?? ''),
                'conclusion' => (string)($workflowJob['conclusion'] ?? ''),
                'url' => (string)($workflowJob['html_url'] ?? ''),
                'runner_name' => (string)($workflowJob['runner_name'] ?? ''),
                'started_at' => (string)($workflowJob['started_at'] ?? ''),
                'completed_at' => (string)($workflowJob['completed_at'] ?? '')
            ]
        ];
    }

    private function labelNames(array $labels): array
    {
        $out = [];
        foreach ($labels as $label)
        {
            if (is_array($label) && isset($label['name'])) $out[] = (string)$label['name'];
            elseif (is_string($label)) $out[] = $label;
        }
        return $out;
    }

    private function branchFromRef(string $ref): string
    {
        foreach (['refs/heads/' => '', 'refs/tags/' => 'tag:'] as $prefix => $replacement)
        {
            if (str_starts_with($ref, $prefix)) return $replacement . substr($ref, strlen($prefix));
        }
        return $ref;
    }
}
