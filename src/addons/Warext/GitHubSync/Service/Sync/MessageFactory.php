<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;
use Warext\GitHubSync\Service\Sync\Traits\MessageFactoryContextTrait;
use Warext\GitHubSync\Service\Sync\Traits\MessageFactoryDefaultsTrait;

final class MessageFactory
{
    use MessageFactoryContextTrait, MessageFactoryDefaultsTrait;

    public function __construct(
        private TemplateRenderer $renderer,
        private ?UserMapper $userMapper = null,
        private ?LabelPrefixMapper $labelPrefixMapper = null,
        private ?FieldMapper $fieldMapper = null
    )
    {
        $this->userMapper ??= new UserMapper();
        $this->labelPrefixMapper ??= new LabelPrefixMapper();
        $this->fieldMapper ??= new FieldMapper();
    }

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

        $action = [
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

        return $this->fieldMapper->applyInbound($mapping, $event, $action);
    }
}
