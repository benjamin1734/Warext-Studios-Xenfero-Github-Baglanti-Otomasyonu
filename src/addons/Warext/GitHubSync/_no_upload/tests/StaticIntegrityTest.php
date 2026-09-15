<?php

$root = dirname(__DIR__, 2);
$errors = [];

$addon = json_decode((string)file_get_contents($root . '/addon.json'), true);
if (!is_array($addon) || ($addon['version_string'] ?? '') !== '0.4.0 Alpha 4') $errors[] = 'addon.json version mismatch';

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
    'wgh_delivery_view.html', 'wgh_diagnostics.html', 'wgh_macros.html'
] as $template)
{
    if (!is_file($root . '/_output/templates/admin/' . $template)) $errors[] = 'Missing admin template: ' . $template;
}

foreach ([
    'Listener.php',
    'Job/ProcessOutbound.php',
    'Service/Sync/OutboundQueue.php',
    'Service/Sync/OutboundProcessor.php',
    'Service/Sync/SyncGuard.php'
] as $file)
{
    if (!is_file($root . '/' . $file)) $errors[] = 'Missing bidirectional sync file: ' . $file;
}

$setup = (string)file_get_contents($root . '/Setup.php');
foreach (['xf_wgh_connection', 'xf_wgh_repository', 'xf_wgh_mapping', 'xf_wgh_template', 'xf_wgh_sync_object', 'xf_wgh_delivery'] as $table)
{
    if (!str_contains($setup, $table)) $errors[] = 'Setup missing table: ' . $table;
}

$messageFactory = (string)file_get_contents($root . '/Service/Sync/MessageFactory.php');
foreach (['issues', 'issue_comment', 'pull_request', 'pull_request_review_comment'] as $event)
{
    if (!str_contains($messageFactory, "event === '" . $event . "'")) $errors[] = 'MessageFactory missing event: ' . $event;
}

if ($errors)
{
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "static-integrity: OK\n";
