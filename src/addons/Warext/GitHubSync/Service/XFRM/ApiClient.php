<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;

final class ApiClient
{
    public function __construct(private CredentialProvider $credentials) {}

    public function getResource(string $keyRef, int $userId, int $resourceId, bool $bypass = false): array
    {
        return $this->request($keyRef, $userId, 'GET', '/resources/' . $resourceId . '/', [], $bypass);
    }

    public function createVersion(
        string $keyRef,
        int $userId,
        int $resourceId,
        string $versionString,
        string $downloadUrl,
        bool $bypass = false
    ): array
    {
        $form = [
            'resource_id' => $resourceId,
            'version_string' => $versionString
        ];
        if ($downloadUrl !== '') $form['external_download_url'] = $downloadUrl;
        return $this->request($keyRef, $userId, 'POST', '/resource-versions/', $form, $bypass);
    }

    public function deleteVersion(string $keyRef, int $userId, int $versionId, bool $hardDelete = false, bool $bypass = false): array
    {
        $form = $hardDelete ? ['hard_delete' => 1] : [];
        return $this->request($keyRef, $userId, 'DELETE', '/resource-versions/' . $versionId . '/', $form, $bypass);
    }

    public function createUpdate(
        string $keyRef,
        int $userId,
        int $resourceId,
        string $title,
        string $message,
        bool $bypass = false
    ): array
    {
        return $this->request($keyRef, $userId, 'POST', '/resource-updates/', [
            'resource_id' => $resourceId,
            'title' => $title,
            'message' => $message
        ], $bypass);
    }

    public function updateUpdate(
        string $keyRef,
        int $userId,
        int $updateId,
        string $title,
        string $message,
        bool $bypass = false
    ): array
    {
        return $this->request($keyRef, $userId, 'POST', '/resource-updates/' . $updateId . '/', [
            'title' => $title,
            'message' => $message
        ], $bypass);
    }

    public function deleteUpdate(string $keyRef, int $userId, int $updateId, bool $hardDelete = false, bool $bypass = false): array
    {
        $form = $hardDelete ? ['hard_delete' => 1] : [];
        return $this->request($keyRef, $userId, 'DELETE', '/resource-updates/' . $updateId . '/', $form, $bypass);
    }

    public function idFromResponse(array $data, string $kind): int
    {
        $keys = $kind === 'version'
            ? ['resource_version_id', 'version_id']
            : ['resource_update_id', 'update_id'];
        $containers = $kind === 'version'
            ? ['resource_version', 'version']
            : ['resource_update', 'update'];

        foreach ($keys as $key)
        {
            if (isset($data[$key]) && (int)$data[$key] > 0) return (int)$data[$key];
        }
        foreach ($containers as $container)
        {
            $item = $data[$container] ?? null;
            if (!is_array($item)) continue;
            foreach ($keys as $key)
            {
                if (isset($item[$key]) && (int)$item[$key] > 0) return (int)$item[$key];
            }
        }
        return 0;
    }

    private function request(string $keyRef, int $userId, string $method, string $path, array $form, bool $bypass): array
    {
        $boardUrl = rtrim((string)\XF::options()->boardUrl, '/');
        if ($boardUrl === '') throw new RuntimeException('XenForo board URL is not configured.');

        $headers = [
            'Accept' => 'application/json',
            'XF-Api-Key' => $this->credentials->apiKey($keyRef),
            'User-Agent' => 'Warext-GitHub-Sync/0.7'
        ];
        if ($userId > 0) $headers['XF-Api-User'] = (string)$userId;
        if ($bypass) $form['api_bypass_permissions'] = 1;

        $options = [
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => 25,
            'connect_timeout' => 8
        ];
        if ($form !== []) $options['form_params'] = $form;

        $response = \XF::app()->http()->client()->request(
            strtoupper($method),
            $boardUrl . '/api' . $path,
            $options
        );
        $status = (int)$response->getStatusCode();
        $raw = (string)$response->getBody();
        $data = $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($data)) $data = [];

        if ($status < 200 || $status >= 300)
        {
            $errors = $data['errors'] ?? [];
            $message = '';
            if (is_array($errors) && isset($errors[0]['message'])) $message = (string)$errors[0]['message'];
            if ($message === '') $message = 'HTTP ' . $status;
            throw new RuntimeException('XFRM API request failed: ' . $message . ' (' . $status . ')');
        }
        return $data;
    }
}
