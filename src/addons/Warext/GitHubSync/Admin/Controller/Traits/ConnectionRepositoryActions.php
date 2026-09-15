<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use XF\Mvc\ParameterBag;
use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;
use Warext\GitHubSync\Service\GitHub\RepositorySynchronizer;

trait ConnectionRepositoryActions
{
    public function actionConnections()
    {
        return $this->view('Warext\\GitHubSync:GitHubSync\\Connections', 'wgh_connections', [
            'connections' => \XF::finder('Warext\\GitHubSync:Connection')->order('title')->fetch()
        ]);
    }

    public function actionConnectionEdit(ParameterBag $params)
    {
        $id = (int)($params->connection_id ?: $this->filter('connection_id', 'uint'));
        $connection = $id ? $this->assertConnectionExists($id) : \XF::em()->create('Warext\\GitHubSync:Connection');
        if (!$connection->exists()) { $connection->provider = 'github_app'; $connection->active = true; }
        return $this->view('Warext\\GitHubSync:GitHubSync\\ConnectionEdit', 'wgh_connection_edit', ['connection' => $connection]);
    }

    public function actionConnectionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $input = $this->filter([
            'title' => 'str', 'app_id' => 'uint', 'installation_id' => 'uint', 'client_id' => 'str',
            'secret_ref' => 'str', 'private_key_ref' => 'str', 'account_login' => 'str', 'active' => 'bool'
        ]);
        $id = (int)($params->connection_id ?: $this->filter('connection_id', 'uint'));
        $connection = $id ? $this->assertConnectionExists($id) : \XF::em()->create('Warext\\GitHubSync:Connection');
        $isNew = !$connection->exists();
        $connection->bulkSet($input);
        $connection->provider = 'github_app';
        if ($isNew) $connection->created_date = \XF::$time;
        $connection->updated_date = \XF::$time;
        $connection->save();
        return $this->redirect($this->buildLink('github-sync/connections'));
    }

    public function actionConnectionDelete(ParameterBag $params)
    {
        $id = (int)($params->connection_id ?: $this->filter('connection_id', 'uint'));
        $connection = $this->assertConnectionExists($id);
        if ($this->isPost())
        {
            if (\XF::finder('Warext\\GitHubSync:Repository')->where('connection_id', $id)->total() > 0)
                return $this->error('This connection still owns synchronized repositories. Disable it or migrate/remove repository records first.');
            $connection->delete();
            return $this->redirect($this->buildLink('github-sync/connections'));
        }
        return $this->view('Warext\\GitHubSync:GitHubSync\\ConnectionDelete', 'wgh_connection_delete', ['connection' => $connection]);
    }

    public function actionConnectionSync(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id = (int)($params->connection_id ?: $this->filter('connection_id', 'uint'));
        $connection = $this->assertConnectionExists($id);
        try
        {
            $result = (new RepositorySynchronizer(new ApiClient(new JwtFactory(), new CredentialProvider())))->synchronize($connection);
            return $this->redirect($this->buildLink('github-sync/repositories'), sprintf(
                'Repository sync complete: %d created, %d updated, %d deactivated.',
                $result['created'], $result['updated'], $result['deactivated']
            ));
        }
        catch (\Throwable $e) { return $this->error('Repository sync failed: ' . $e->getMessage()); }
    }

    public function actionRepositories()
    {
        $connectionId = $this->filter('connection_id', 'uint');
        $finder = \XF::finder('Warext\\GitHubSync:Repository')->order('full_name');
        if ($connectionId) $finder->where('connection_id', $connectionId);
        return $this->view('Warext\\GitHubSync:GitHubSync\\Repositories', 'wgh_repositories', [
            'repositories' => $finder->fetch(),
            'connections' => \XF::finder('Warext\\GitHubSync:Connection')->order('title')->fetch(),
            'connectionId' => $connectionId
        ]);
    }
}
