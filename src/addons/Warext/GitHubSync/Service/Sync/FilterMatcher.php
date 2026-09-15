<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Entity\Mapping;

final class FilterMatcher
{
    public function matches(Mapping $mapping, WebhookEvent $event): bool
    {
        $config = is_array($mapping->filter_config) ? $mapping->filter_config : [];
        if ($config === [])
        {
            return true;
        }

        return $this->matchBranches($config, $event)
            && $this->matchReleaseState($config, $event)
            && $this->matchCommitMessages($config, $event)
            && $this->matchChangedPaths($config, $event)
            && $this->matchWorkflowState($config, $event);
    }

    private function matchBranches(array $config, WebhookEvent $event): bool
    {
        $allowed = array_values(array_filter((array)($config['branches'] ?? []), 'is_string'));
        if ($allowed === []) return true;

        $branch = match ($event->event)
        {
            'push' => (string)($event->payload['ref'] ?? ''),
            'pull_request', 'pull_request_review', 'pull_request_review_comment' => (string)($event->payload['pull_request']['base']['ref'] ?? ''),
            'workflow_run' => (string)($event->payload['workflow_run']['head_branch'] ?? ''),
            'workflow_job' => (string)($event->payload['workflow_job']['head_branch'] ?? ''),
            default => ''
        };
        if ($branch === '') return true;
        if (str_starts_with($branch, 'refs/heads/')) $branch = substr($branch, 11);
        return in_array($branch, $allowed, true);
    }

    private function matchReleaseState(array $config, WebhookEvent $event): bool
    {
        if ($event->event !== 'release')
        {
            return true;
        }

        $release = (array)($event->payload['release'] ?? []);
        if (empty($config['allow_prerelease']) && !empty($release['prerelease']))
        {
            return false;
        }
        if (empty($config['allow_draft']) && !empty($release['draft']))
        {
            return false;
        }
        return true;
    }

    private function matchCommitMessages(array $config, WebhookEvent $event): bool
    {
        if ($event->event !== 'push')
        {
            return true;
        }

        $messages = [];
        foreach ((array)($event->payload['commits'] ?? []) as $commit)
        {
            if (is_array($commit))
            {
                $messages[] = mb_strtolower((string)($commit['message'] ?? ''));
            }
        }
        $haystack = implode("\n", $messages);

        foreach ((array)($config['exclude_commit'] ?? $config['message_excludes'] ?? []) as $needle)
        {
            $needle = mb_strtolower(trim((string)$needle));
            if ($needle !== '' && str_contains($haystack, $needle))
            {
                return false;
            }
        }

        $includes = array_values(array_filter(array_map(
            static fn($v) => mb_strtolower(trim((string)$v)),
            (array)($config['include_commit'] ?? $config['message_includes'] ?? [])
        )));
        if ($includes === [])
        {
            return true;
        }

        foreach ($includes as $needle)
        {
            if (str_contains($haystack, $needle))
            {
                return true;
            }
        }
        return false;
    }

    private function matchChangedPaths(array $config, WebhookEvent $event): bool
    {
        if ($event->event !== 'push')
        {
            return true;
        }

        $paths = [];
        foreach ((array)($event->payload['commits'] ?? []) as $commit)
        {
            if (!is_array($commit))
            {
                continue;
            }
            foreach (['added', 'modified', 'removed'] as $key)
            {
                foreach ((array)($commit[$key] ?? []) as $path)
                {
                    if (is_string($path) && $path !== '')
                    {
                        $paths[$path] = true;
                    }
                }
            }
        }
        if ($paths === [])
        {
            return true;
        }

        $includes = array_values(array_filter((array)($config['include_paths'] ?? []), 'is_string'));
        $excludes = array_values(array_filter((array)($config['exclude_paths'] ?? $config['ignore_paths'] ?? []), 'is_string'));
        $meaningful = 0;

        foreach (array_keys($paths) as $path)
        {
            $excluded = $this->matchesAny($excludes, $path);
            if ($excluded)
            {
                continue;
            }
            if ($includes !== [] && !$this->matchesAny($includes, $path))
            {
                continue;
            }
            $meaningful++;
        }

        return $meaningful > 0;
    }

    private function matchWorkflowState(array $config, WebhookEvent $event): bool
    {
        if (!in_array($event->event, ['workflow_run', 'workflow_job'], true)) return true;

        $workflow = $event->event === 'workflow_run'
            ? (array)($event->payload['workflow_run'] ?? [])
            : (array)($event->payload['workflow_job'] ?? []);
        $status = mb_strtolower((string)($workflow['status'] ?? ''));
        $conclusion = mb_strtolower((string)($workflow['conclusion'] ?? ''));

        $statuses = array_values(array_filter(array_map(static fn($v) => mb_strtolower(trim((string)$v)), (array)($config['workflow_statuses'] ?? []))));
        if ($statuses !== [] && $status !== '' && !in_array($status, $statuses, true)) return false;

        $conclusions = array_values(array_filter(array_map(static fn($v) => mb_strtolower(trim((string)$v)), (array)($config['workflow_conclusions'] ?? []))));
        if ($conclusions !== [] && $conclusion !== '' && !in_array($conclusion, $conclusions, true)) return false;

        return true;
    }

    private function matchesAny(array $patterns, string $value): bool
    {
        foreach ($patterns as $pattern)
        {
            if ($this->globMatches((string)$pattern, $value))
            {
                return true;
            }
        }
        return false;
    }

    private function globMatches(string $pattern, string $value): bool
    {
        $quoted = preg_quote(trim($pattern), '#');
        $quoted = str_replace(['\\*\\*', '\\*'], ['.*', '[^/]*'], $quoted);
        return (bool)preg_match('#^' . $quoted . '$#i', $value);
    }
}
