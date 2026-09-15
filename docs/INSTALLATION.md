# Development installation

> This is an Alpha development source tree. Do not treat it as a production XenForo release package yet.

## Requirements

- XenForo 2.3+
- PHP version supported by the target XenForo release
- OpenSSL extension
- JSON extension
- HTTPS forum URL reachable by GitHub
- A GitHub App installed on the repositories you want to synchronize

## Place the source

Copy:

```text
src/addons/Warext/GitHubSync
```

to the same path in your XenForo installation.

Enable XenForo development mode and import the add-on development output using your normal XenForo development workflow.

## Secret configuration

Store real secrets in `src/config.php`, not in the database/repository:

```php
$config['warextGitHubSync'] = [
    'webhookSecret' => 'optional-bootstrap-secret',

    'webhookSecrets' => [
        'warext_app' => 'replace-with-real-random-webhook-secret'
    ],

    'privateKeys' => [
        'warext_app' => <<<'PEM'
-----BEGIN PRIVATE KEY-----
...
-----END PRIVATE KEY-----
PEM
    ],

    'pushAggregationSeconds' => 60
];
```

In Admin CP create a GitHub connection and use `warext_app` as the private-key reference and webhook-secret reference.

## GitHub App

Configure the App with only the permissions required by the modules you enable. Current Issue bidirectional synchronization requires Issues read/write. Inbound Pull Request modules require appropriate Pull Requests read access. Repository metadata access is also required for installation repository discovery.

Webhook URL:

```text
https://forum.example.com/github-sync-webhook
```

Subscribe only to events you use. Current modules understand release, push, issues, issue_comment, pull_request, pull_request_review and pull_request_review_comment.

## Mapping examples

### GitHub Issue → XenForo forum

- Direction: `GitHub → XenForo` or `Bidirectional`
- Event: `issues`
- Event action: `*`
- XenForo target forum node ID: your bug-report forum
- Automation user ID: your bot account

### XenForo forum → GitHub Issues

- Direction: `XenForo → GitHub` or `Bidirectional`
- Repository: choose one concrete repository
- XenForo source forum node ID: your bug-report forum
- GitHub object: `Issue`
- Enable first-post/reply/state synchronization as required

For a true bidirectional Issue workflow, use a concrete repository and configure both the inbound target forum and outbound source forum consistently.

## GitHub App permissions for bidirectional Issue sync

For the current feature set, grant the GitHub App repository **Metadata: Read-only** and **Issues: Read and write** permissions. Subscribe only to the webhook events enabled by your mappings (for example Releases, Push, Issues, Issue comments, Pull requests and Pull request review events).

## Optional XFRM API key

For GitHub Release → XFRM synchronization, create a dedicated XenForo API key with Resource Manager write scope and reference it from config:

```php
$config['warextGitHubSync']['xfrmApiKeys'] = [
    'github_xfrm' => 'REAL-XENFORO-API-KEY'
];
```

The mapping stores only `github_xfrm`. The real API key is never saved to the add-on database.
