<?php

namespace Warext\GitHubSync\Pub\Controller;

use Warext\GitHubSync\Service\Webhook\DeliveryIngestor;
use Warext\GitHubSync\Service\Webhook\EventNormalizer;
use Warext\GitHubSync\Service\Webhook\HeaderReader;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\Webhook\SecretResolver;
use Warext\GitHubSync\Service\Webhook\SignatureVerifier;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Webhook extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        if (!$this->request->isPost())
        {
            return $this->message('Warext GitHub Sync webhook endpoint is online. POST required.');
        }

        $payload = (string)$this->request->getInputRaw();
        $headers = new HeaderReader();
        $secrets = (new SecretResolver(new CredentialProvider()))->resolveCandidates($payload);

        if ($secrets === [])
        {
            return $this->error('GitHub webhook secret is not configured.', 503);
        }

        try
        {
            $ingestor = new DeliveryIngestor(new SignatureVerifier(), new EventNormalizer());
            $result = $ingestor->ingest(
                $payload,
                $headers->get('X-GitHub-Delivery'),
                $headers->get('X-GitHub-Event'),
                $headers->get('X-Hub-Signature-256'),
                $secrets
            );
        }
        catch (\Throwable $e)
        {
            \XF::logException($e, false, 'Warext GitHub Sync webhook: ');
            return $this->error('Webhook rejected.', 403);
        }

        return $this->json([
            'ok' => true,
            'duplicate' => $result['duplicate'],
            'delivery_id' => $result['delivery_id'],
            'event' => $result['event'],
            'action' => $result['action']
        ]);
    }

    public function checkCsrfIfNeeded($action, ParameterBag $params): void
    {
        // External GitHub webhook endpoint. Authenticity is enforced by HMAC-SHA256.
    }
}
