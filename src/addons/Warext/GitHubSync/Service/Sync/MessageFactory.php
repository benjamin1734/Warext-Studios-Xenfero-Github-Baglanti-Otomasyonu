<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

final class MessageFactory
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ?UserMapper $userMapper = null,
        private ?LabelPrefixMapper $labelPrefixMapper = null
    )
    {
        $this->userMapper ??= new UserMapper();
        $this->labelPrefixMapper ??= new LabelPrefixMapper();
    }

    /** @return array{title:string,message:string,object_type:string,object_id:string,node_id:int,thread_id:int,user_id:int,prefix_id:int,update_first_post:bool,update_title:bool,action_mode:string,delete_policy:string} */
    public function build(Mapping $mapping, Repository $repository, WebhookEvent $event): array
    {
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        $vars = $this->variables($repository, $event);
        $defaults = $this->defaults($event);

        $titleTemplate = (string)($target['title_template'] ?? '');
        $messageTemplate = (string)($target['message_template'] ?? '');
        $templateId = (int)($target['template_id'] ?? 0);

        if ($templateId > 0)
        {
            $savedTemplate = \XF::em()->find('Warext\\GitHubSync:Template', $templateId);
            if ($savedTemplate && $savedTemplate->active)
            {
                if ($titleTemplate === '') $titleTemplate = (string)$savedTemplate->title_template;
                if ($messageTemplate === '') $messageTemplate = (string)$savedTemplate->message_template;
            }
        }

        if ($titleTemplate === '') $titleTemplate = $defaults['title'];
        if ($messageTemplate === '') $messageTemplate = $defaults['message'];

        $userId = (int)($target['user_id'] ?? 0);
        if (!empty($target['use_user_mapping']))
        {
            $senderLogin = (string)($event->payload['sender']['login'] ?? '');
            $userId = $this->userMapper->resolveXfUserId($repository, $senderLogin, $userId);
        }

        $prefixId = (int)($target['prefix_id'] ?? 0);
        if (!empty($target['sync_remote_labels']))
        {
            $prefixId = $this->labelPrefixMapper->prefixForLabels($mapping, $this->eventLabels($event), $prefixId);
        }

        return [
            'title' => trim($this->renderer->render($titleTemplate, $vars)),
            'message' => trim($this->renderer->render($messageTemplate, $vars)),
            'object_type' => $defaults['object_type'],
            'object_id' => $defaults['object_id'],
            'node_id' => (int)($target['node_id'] ?? 0),
            'thread_id' => (int)($target['thread_id'] ?? 0),
            'user_id' => $userId,
            'prefix_id' => $prefixId,
            'update_first_post' => (bool)($target['update_first_post'] ?? false),
            'update_title' => (bool)($target['update_title'] ?? false),
            'action_mode' => $defaults['action_mode'],
            'delete_policy' => (string)($target['delete_policy'] ?? 'mark_deleted'),
            'parent_object_type' => (string)($defaults['parent_object_type'] ?? ''),
            'parent_object_id' => (string)($defaults['parent_object_id'] ?? ''),
            'sync_metadata' => (array)($defaults['sync_metadata'] ?? []),
            'remote_state' => !empty($target['sync_remote_state']) ? $this->remoteState($event) : '',
            'sync_remote_labels' => !empty($target['sync_remote_labels']),
            'remote_updated_at' => $this->remoteUpdatedAt($event)
        ];
    }

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
            ]
        ];
    }

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
            default => ''
        };
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
