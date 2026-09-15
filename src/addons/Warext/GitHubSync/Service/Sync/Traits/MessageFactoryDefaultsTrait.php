<?php

namespace Warext\GitHubSync\Service\Sync\Traits;

use Warext\GitHubSync\Dto\WebhookEvent;

trait MessageFactoryDefaultsTrait
{
    private function defaults(WebhookEvent $event): array
    {
        if ($event->event === 'release')
        {
            $release = (array)($event->payload['release'] ?? []);
            return [
                'title' => '{repo.name} {release.tag}',
                'message' => "[HEADING=2]{release.name} ({release.tag})[/HEADING]\n\n{release.body}\n\n[URL=\"{release.url}\"]GitHub Release[/URL]",
                'object_type' => 'release',
                'object_id' => (string)($release['id'] ?? ''),
                'action_mode' => $event->action === 'deleted' ? 'delete' : 'upsert'
            ];
        }

        if ($event->event === 'push')
        {
            $ref = (string)($event->payload['ref'] ?? '');
            $isTag = str_starts_with($ref, 'refs/tags/');
            return [
                'title' => '{repo.name} - {push.branch}',
                'message' => "[B]{push.commit_count} yeni commit[/B] — {push.branch}\n\n{push.commits}\n\n[URL=\"{push.compare_url}\"]Değişiklikleri GitHub'da görüntüle[/URL]",
                'object_type' => $isTag ? 'tag' : 'push',
                'object_id' => $isTag ? $ref : (string)($event->payload['after'] ?? $event->deliveryGuid),
                'action_mode' => !empty($event->payload['deleted']) ? 'delete' : 'upsert'
            ];
        }

        if ($event->event === 'issues')
        {
            $issue = (array)($event->payload['issue'] ?? []);
            return [
                'title' => '[#{issue.number}] {issue.title}',
                'message' => "[HEADING=2]{issue.title}[/HEADING]\n\n{issue.body}\n\n[B]Durum:[/B] {issue.state}\n[B]Etiketler:[/B] {issue.labels}\n[B]GitHub:[/B] [URL=\"{issue.url}\"]#{issue.number}[/URL]",
                'object_type' => 'issue',
                'object_id' => (string)($issue['id'] ?? ''),
                'action_mode' => $event->action === 'deleted' ? 'delete' : 'upsert',
                'sync_metadata' => ['issue_number' => (int)($issue['number'] ?? 0)]
            ];
        }

        if ($event->event === 'issue_comment')
        {
            $comment = (array)($event->payload['comment'] ?? []);
            $issue = (array)($event->payload['issue'] ?? []);
            $isPr = !empty($issue['pull_request']);
            return [
                'title' => $isPr ? 'PR #{issue.number} yorumu' : 'Issue #{issue.number} yorumu',
                'message' => "[B]{comment.user}[/B]\n\n{comment.body}\n\n[URL=\"{comment.url}\"]GitHub yorumunu görüntüle[/URL]",
                'object_type' => $isPr ? 'pr_comment' : 'issue_comment',
                'object_id' => (string)($comment['id'] ?? ''),
                'action_mode' => $event->action === 'deleted' ? 'delete' : 'upsert',
                'parent_object_type' => $isPr ? 'pull_request' : 'issue',
                'parent_object_id' => $isPr ? ('number:' . (string)($issue['number'] ?? '')) : (string)($issue['id'] ?? '')
            ];
        }

        if ($event->event === 'pull_request')
        {
            $pr = (array)($event->payload['pull_request'] ?? []);
            return [
                'title' => '[PR #{pr.number}] {pr.title}',
                'message' => "[HEADING=2]{pr.title}[/HEADING]\n\n{pr.body}\n\n[B]Durum:[/B] {pr.state}\n[B]Branch:[/B] {pr.head} → {pr.base}\n[B]GitHub:[/B] [URL=\"{pr.url}\"]PR #{pr.number}[/URL]",
                'object_type' => 'pull_request',
                'object_id' => 'number:' . (string)($pr['number'] ?? ''),
                'action_mode' => $event->action === 'deleted' ? 'delete' : 'upsert',
                'sync_metadata' => ['pull_request_number' => (int)($pr['number'] ?? 0)]
            ];
        }

        if ($event->event === 'pull_request_review_comment')
        {
            $comment = (array)($event->payload['comment'] ?? []);
            return [
                'title' => 'PR #{pr.number} inceleme yorumu',
                'message' => "[B]{comment.user}[/B]\n\n{comment.body}\n\n[URL=\"{comment.url}\"]GitHub inceleme yorumunu görüntüle[/URL]",
                'object_type' => 'pr_review_comment',
                'object_id' => (string)($comment['id'] ?? ''),
                'action_mode' => $event->action === 'deleted' ? 'delete' : 'upsert',
                'parent_object_type' => 'pull_request',
                'parent_object_id' => 'number:' . (string)($event->payload['pull_request']['number'] ?? '')
            ];
        }

        if ($event->event === 'pull_request_review')
        {
            $review = (array)($event->payload['review'] ?? []);
            return [
                'title' => 'PR #{pr.number} incelemesi',
                'message' => "[B]{review.user} — {review.state}[/B]\n\n{review.body}\n\n[URL=\"{review.url}\"]GitHub incelemesini görüntüle[/URL]",
                'object_type' => 'pr_review',
                'object_id' => (string)($review['id'] ?? ''),
                'action_mode' => $event->action === 'dismissed' ? 'delete' : 'upsert',
                'parent_object_type' => 'pull_request',
                'parent_object_id' => 'number:' . (string)($event->payload['pull_request']['number'] ?? '')
            ];
        }

        if ($event->event === 'workflow_run')
        {
            $run = (array)($event->payload['workflow_run'] ?? []);
            return [
                'title' => '[Actions #{workflow.run_number}] {workflow.name} — {workflow.status}',
                'message' => "[HEADING=2]{workflow.name}[/HEADING]\n\n[B]Durum:[/B] {workflow.status}\n[B]Sonuç:[/B] {workflow.conclusion}\n[B]Branch:[/B] {workflow.branch}\n[B]SHA:[/B] {workflow.sha}\n\n[URL=\"{workflow.url}\"]GitHub Actions çalışmasını görüntüle[/URL]",
                'object_type' => 'workflow_run',
                'object_id' => (string)($run['id'] ?? ''),
                'action_mode' => 'upsert',
                'sync_metadata' => ['workflow_run_id' => (int)($run['id'] ?? 0)]
            ];
        }

        if ($event->event === 'workflow_job')
        {
            $job = (array)($event->payload['workflow_job'] ?? []);
            return [
                'title' => 'Actions job: {job.name}',
                'message' => "[B]{job.name}[/B]\n\n[B]Durum:[/B] {job.status}\n[B]Sonuç:[/B] {job.conclusion}\n[B]Runner:[/B] {job.runner_name}\n\n[URL=\"{job.url}\"]GitHub Actions jobunu görüntüle[/URL]",
                'object_type' => 'workflow_job',
                'object_id' => (string)($job['id'] ?? ''),
                'action_mode' => 'upsert',
                'parent_object_type' => 'workflow_run',
                'parent_object_id' => (string)($job['run_id'] ?? ''),
                'sync_metadata' => ['workflow_run_id' => (int)($job['run_id'] ?? 0)]
            ];
        }

        return [
            'title' => '{repo.name} - {event}',
            'message' => '[B]{repo.full_name}[/B] repository olayı: {event}.{action}',
            'object_type' => $event->event,
            'object_id' => $event->deliveryGuid,
            'action_mode' => 'upsert'
        ];
    }

    private function eventLabels(WebhookEvent $event): array
    {
        if ($event->event === 'issues' || $event->event === 'issue_comment')
        {
            return (array)($event->payload['issue']['labels'] ?? []);
        }
        if (str_starts_with($event->event, 'pull_request'))
        {
            return (array)($event->payload['pull_request']['labels'] ?? []);
        }
        return [];
    }

    private function remoteState(WebhookEvent $event): string
    {
        if ($event->event === 'issues' || $event->event === 'issue_comment')
        {
            return (string)($event->payload['issue']['state'] ?? '');
        }
        if (str_starts_with($event->event, 'pull_request'))
        {
            return (string)($event->payload['pull_request']['state'] ?? '');
        }
        return '';
    }

    private function remoteUpdatedAt(WebhookEvent $event): string
    {
        $p = $event->payload;
        return match ($event->event)
        {
            'issues' => (string)($p['issue']['updated_at'] ?? ''),
            'issue_comment' => (string)($p['comment']['updated_at'] ?? $p['issue']['updated_at'] ?? ''),
            'pull_request' => (string)($p['pull_request']['updated_at'] ?? ''),
            'pull_request_review' => (string)($p['review']['submitted_at'] ?? $p['pull_request']['updated_at'] ?? ''),
            'pull_request_review_comment' => (string)($p['comment']['updated_at'] ?? $p['pull_request']['updated_at'] ?? ''),
            'release' => (string)($p['release']['updated_at'] ?? $p['release']['published_at'] ?? ''),
            'workflow_run' => (string)($p['workflow_run']['updated_at'] ?? $p['workflow_run']['run_started_at'] ?? ''),
            'workflow_job' => (string)($p['workflow_job']['completed_at'] ?? $p['workflow_job']['started_at'] ?? ''),
            default => ''
        };
    }
}
