<?php

$root = dirname(__DIR__, 2);
$errors = [];

$addon = json_decode((string)file_get_contents($root . '/addon.json'), true);
if (!is_array($addon) || ($addon['version_string'] ?? '') !== '0.6.0 Alpha 6') $errors[] = 'addon.json version mismatch';

foreach ([
    '_output/routes/admin/github-sync.json',
    '_output/routes/public/github-sync-webhook.json',
    '_output/admin_navigation/wghGitHubSync.json',
    '_output/admin_permissions/wghManage.json',
    '_output/code_event_listeners/entity_post_save_thread.json',
    '_output/code_event_listeners/entity_post_save_post.json',
    '_output/code_event_listeners/entity_post_delete_thread.json',
    '_output/code_event_listeners/entity_post_delete_post.json'
] as $jsonFile)
{
    $data = json_decode((string)file_get_contents($root . '/' . $jsonFile), true);
    if (!is_array($data)) $errors[] = 'Invalid JSON: ' . $jsonFile;
}

foreach ([
    'wgh_dashboard.html', 'wgh_connections.html', 'wgh_connection_edit.html',
    'wgh_repositories.html', 'wgh_mappings.html', 'wgh_mapping_edit.html',
    'wgh_templates.html', 'wgh_template_edit.html', 'wgh_deliveries.html',
    'wgh_delivery_view.html', 'wgh_diagnostics.html', 'wgh_macros.html',
    'wgh_user_mappings.html', 'wgh_user_mapping_edit.html', 'wgh_user_mapping_delete.html',
    'wgh_conflicts.html', 'wgh_conflict_view.html', 'wgh_actions.html'
] as $template)
{
    if (!is_file($root . '/_output/templates/admin/' . $template)) $errors[] = 'Missing admin template: ' . $template;
}

foreach ([
    'Listener.php',
    'Job/ProcessOutbound.php',
    'Service/Sync/OutboundQueue.php',
    'Service/Sync/OutboundProcessor.php',
    'Service/Sync/SyncGuard.php',
    'Service/Sync/ConflictResolver.php',
    'Service/Sync/LabelPrefixMapper.php',
    'Service/Sync/UserMapper.php',
    'Service/Sync/FieldMapper.php',
    'Admin/Controller/Traits/WorkflowActions.php',
    'Entity/Conflict.php',
    'Entity/UserMapping.php'
] as $file)
{
    if (!is_file($root . '/' . $file)) $errors[] = 'Missing synchronization file: ' . $file;
}

$setup = (string)file_get_contents($root . '/Setup.php');
foreach ([
    'xf_wgh_connection', 'xf_wgh_repository', 'xf_wgh_mapping', 'xf_wgh_template',
    'xf_wgh_sync_object', 'xf_wgh_delivery', 'xf_wgh_user_mapping', 'xf_wgh_conflict'
] as $table)
{
    if (!str_contains($setup, $table)) $errors[] = 'Setup missing table: ' . $table;
}
foreach (['upgrade5000050Step1', 'upgrade5000050Step2'] as $upgrade)
{
    if (!str_contains($setup, $upgrade)) $errors[] = 'Setup missing upgrade step: ' . $upgrade;
}

$messageFactory = (string)file_get_contents($root . '/Service/Sync/MessageFactory.php');
foreach (glob($root . '/Service/Sync/Traits/MessageFactory*.php') ?: [] as $traitFile) $messageFactory .= (string)file_get_contents($traitFile);
foreach (['issues', 'issue_comment', 'pull_request', 'pull_request_review_comment', 'workflow_run', 'workflow_job'] as $event)
{
    if (!str_contains($messageFactory, "event === '" . $event . "'")) $errors[] = 'MessageFactory missing event: ' . $event;
}
foreach (['sync_remote_labels', 'sync_remote_state', 'use_user_mapping'] as $feature)
{
    if (!str_contains($messageFactory, $feature)) $errors[] = 'MessageFactory missing feature: ' . $feature;
}

$dispatcher = (string)file_get_contents($root . '/Service/Sync/EventDispatcher.php');
if (!str_contains($dispatcher, 'ConflictResolver')) $errors[] = 'EventDispatcher does not use ConflictResolver';

$outbound = (string)file_get_contents($root . '/Service/Sync/OutboundProcessor.php');
foreach (glob($root . '/Service/Sync/Traits/Outbound*.php') ?: [] as $traitFile) $outbound .= (string)file_get_contents($traitFile);
foreach (['sync_labels', 'labelsForPrefix', 'currentXfHash', 'pull_request', 'applyReviewPrefix', 'FieldMapper'] as $feature)
{
    if (!str_contains($outbound, $feature)) $errors[] = 'OutboundProcessor missing feature: ' . $feature;
}

$admin = '';
foreach (array_merge(
    [$root . '/Admin/Controller/GitHubSync.php'],
    glob($root . '/Admin/Controller/Traits/*.php') ?: []
) as $adminFile)
{
    $admin .= (string)file_get_contents($adminFile);
}
foreach (['actionUserMappings', 'actionConflicts', 'actionConflictResolve', 'label_prefix_map', 'actionActions', 'actionWorkflowDispatch', 'outbound_field_map', 'inbound_field_map'] as $feature)
{
    if (!str_contains($admin, $feature)) $errors[] = 'Admin controller modules missing feature: ' . $feature;
}

$api = (string)file_get_contents($root . '/Service/GitHub/ApiClient.php');
foreach (['createPullRequest', 'updatePullRequest', 'createPullRequestReview', 'listWorkflows', 'dispatchWorkflow'] as $feature)
{
    if (!str_contains($api, $feature)) $errors[] = 'ApiClient missing feature: ' . $feature;
}

$filters = (string)file_get_contents($root . '/Service/Sync/FilterMatcher.php');
foreach (['workflow_statuses', 'workflow_conclusions', 'workflow_run', 'workflow_job'] as $feature)
{
    if (!str_contains($filters, $feature)) $errors[] = 'FilterMatcher missing workflow feature: ' . $feature;
}

if ($errors)
{
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "static-integrity: OK\n";
