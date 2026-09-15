<?php
$root = dirname(__DIR__, 2);
require $root . '/Service/GitHub/JwtFactory.php';
require $root . '/Service/Webhook/SignatureVerifier.php';
require $root . '/Service/Sync/TemplateRenderer.php';
require $root . '/Service/Sync/SyncGuard.php';

use Warext\GitHubSync\Service\GitHub\JwtFactory;
use Warext\GitHubSync\Service\Webhook\SignatureVerifier;
use Warext\GitHubSync\Service\Sync\TemplateRenderer;
use Warext\GitHubSync\Service\Sync\SyncGuard;

$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
if (!$key) throw new RuntimeException('RSA key generation failed');
openssl_pkey_export($key, $pem);
$jwt = (new JwtFactory())->create(12345, $pem, 1700000000);
$parts = explode('.', $jwt);
if (count($parts) !== 3) throw new RuntimeException('JWT segment count failed');
$decode = fn($v) => base64_decode(strtr($v . str_repeat('=', (4 - strlen($v) % 4) % 4), '-_', '+/'));
$header = json_decode($decode($parts[0]), true, 512, JSON_THROW_ON_ERROR);
$payload = json_decode($decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
if (($header['alg'] ?? '') !== 'RS256' || ($payload['iss'] ?? '') !== '12345' || ($payload['iat'] ?? 0) !== 1699999940 || ($payload['exp'] ?? 0) !== 1700000540) throw new RuntimeException('JWT claims failed');
$details = openssl_pkey_get_details($key);
if (!openssl_verify($parts[0] . '.' . $parts[1], $decode($parts[2]), $details['key'], OPENSSL_ALGO_SHA256)) throw new RuntimeException('JWT signature verification failed');

$payloadText = 'Hello, World!';
$secret = "It's a Secret to Everybody";
$signature = 'sha256=757107ea0eb2509fc211221cce984b8a37570b6d7586c22c46f4379c8b043e17';
$verifier = new SignatureVerifier();
if (!$verifier->verify($payloadText, $signature, $secret)) throw new RuntimeException('GitHub HMAC vector failed');
if (!$verifier->verifyAny($payloadText, $signature, ['wrong', $secret])) throw new RuntimeException('Multi-secret verification failed');
if ($verifier->verifyAny($payloadText, $signature, ['wrong'])) throw new RuntimeException('Invalid secret accepted');

$r = new TemplateRenderer();
$out = $r->render('{repo.name} {issue.number} {comment.user} {missing.value}', [
    'repo' => ['name' => 'Demo'], 'issue' => ['number' => '42'], 'comment' => ['user' => 'octocat']
]);
if ($out !== 'Demo 42 octocat ') throw new RuntimeException('Template renderer failed: ' . $out);

if (SyncGuard::active()) throw new RuntimeException('Sync guard should start inactive');
$value = SyncGuard::run(function() {
    if (!SyncGuard::active()) throw new RuntimeException('Sync guard was not active inside guarded callback');
    return 123;
});
if ($value !== 123 || SyncGuard::active()) throw new RuntimeException('Sync guard cleanup failed');

echo "core-tests: OK\n";
