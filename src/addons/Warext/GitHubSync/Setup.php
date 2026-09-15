<?php

namespace Warext\GitHubSync;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait, StepRunnerUpgradeTrait, StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->createConnection();
        $this->createRepository();
        $this->createMapping();
        $this->createTemplate();
        $this->createSyncObject();
        $this->createDelivery();
        $this->createUserMapping();
        $this->createXfrmRelease(true);
        $this->createXfrmHistory();
        $this->createConflict();
    }

    public function upgrade2000020Step1(): void
    {
        $this->schemaManager()->alterTable('xf_wgh_connection', function (Alter $t)
        {
            $t->addColumn('private_key_ref', 'varchar', 191)->setDefault('');
            $t->addColumn('account_login', 'varchar', 100)->setDefault('');
        });
    }

    public function upgrade3000030Step1(): void
    {
        $this->schemaManager()->alterTable('xf_wgh_delivery', function (Alter $t)
        {
            $t->addColumn('source_ref', 'varchar', 255)->setDefault('')->after('payload_hash');
            $t->addColumn('next_attempt_date', 'int')->unsigned()->setDefault(0)->after('attempt_count');
            $t->addColumn('grouped_into_delivery_id', 'bigint')->unsigned()->setDefault(0)->after('next_attempt_date');
            $t->addKey(['github_repository_id', 'github_event', 'source_ref'], 'repo_event_ref');
        });
    }

    public function upgrade3000030Step2(): void { $this->createTemplate(); }
    public function upgrade5000050Step1(): void { $this->createUserMapping(); }
    public function upgrade5000050Step2(): void { $this->createConflict(); }
    public function upgrade7000070Step1(): void { $this->createXfrmRelease(false); }

    public function upgrade8000080Step1(): void
    {
        $this->schemaManager()->alterTable('xf_wgh_xfrm_release', function (Alter $t)
        {
            $t->addColumn('retry_count', 'int')->unsigned()->setDefault(0);
            $t->addColumn('last_attempt_date', 'int')->unsigned()->setDefault(0);
            $t->addColumn('last_success_date', 'int')->unsigned()->setDefault(0);
            $t->addColumn('last_reconcile_date', 'int')->unsigned()->setDefault(0);
            $t->addColumn('remote_state', 'varchar', 25)->setDefault('unknown');
            $t->addKey(['status', 'last_attempt_date']);
        });
    }

    public function upgrade8000080Step2(): void { $this->createXfrmHistory(); }

    public function uninstallStep1(): void
    {
        $sm = $this->schemaManager();
        foreach ([
            'xf_wgh_xfrm_history', 'xf_wgh_xfrm_release', 'xf_wgh_conflict', 'xf_wgh_user_mapping',
            'xf_wgh_delivery', 'xf_wgh_sync_object', 'xf_wgh_template', 'xf_wgh_mapping',
            'xf_wgh_repository', 'xf_wgh_connection'
        ] as $table) $sm->dropTable($table);
    }

    private function createConnection(): void
    {
        $this->schemaManager()->createTable('xf_wgh_connection', function (Create $t)
        {
            $t->addColumn('connection_id', 'int')->autoIncrement();
            $t->addColumn('title', 'varchar', 100);
            $t->addColumn('provider', 'varchar', 25)->setDefault('github_app');
            $t->addColumn('app_id', 'bigint')->unsigned()->setDefault(0);
            $t->addColumn('installation_id', 'bigint')->unsigned()->setDefault(0);
            $t->addColumn('client_id', 'varchar', 100)->setDefault('');
            $t->addColumn('secret_ref', 'varchar', 191)->setDefault('');
            $t->addColumn('private_key_ref', 'varchar', 191)->setDefault('');
            $t->addColumn('account_login', 'varchar', 100)->setDefault('');
            $t->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $t->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $t->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('connection_id'); $t->addKey('active');
        });
    }

    private function createRepository(): void
    {
        $this->schemaManager()->createTable('xf_wgh_repository', function (Create $t)
        {
            $t->addColumn('repository_id', 'int')->autoIncrement();
            $t->addColumn('connection_id', 'int')->unsigned();
            $t->addColumn('github_repository_id', 'bigint')->unsigned();
            $t->addColumn('full_name', 'varchar', 191);
            $t->addColumn('owner_name', 'varchar', 100)->setDefault('');
            $t->addColumn('repo_name', 'varchar', 100)->setDefault('');
            $t->addColumn('default_branch', 'varchar', 100)->setDefault('main');
            $t->addColumn('html_url', 'varchar', 500)->setDefault('');
            $t->addColumn('is_private', 'tinyint')->unsigned()->setDefault(0);
            $t->addColumn('is_archived', 'tinyint')->unsigned()->setDefault(0);
            $t->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $t->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $t->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('repository_id'); $t->addUniqueKey('github_repository_id'); $t->addKey(['connection_id', 'active']);
        });
    }

    private function createMapping(): void
    {
        $this->schemaManager()->createTable('xf_wgh_mapping', function (Create $t)
        {
            $t->addColumn('mapping_id', 'int')->autoIncrement();
            $t->addColumn('repository_id', 'int')->unsigned()->setDefault(0);
            $t->addColumn('title', 'varchar', 150);
            $t->addColumn('direction', 'enum')->values(['github_to_xf','xf_to_github','bidirectional'])->setDefault('github_to_xf');
            $t->addColumn('event', 'varchar', 75)->setDefault('*');
            $t->addColumn('event_action', 'varchar', 75)->setDefault('*');
            $t->addColumn('source_config', 'mediumblob')->nullable();
            $t->addColumn('target_config', 'mediumblob')->nullable();
            $t->addColumn('filter_config', 'mediumblob')->nullable();
            $t->addColumn('conflict_strategy', 'varchar', 30)->setDefault('manual');
            $t->addColumn('priority', 'int')->setDefault(100);
            $t->addColumn('enabled', 'tinyint')->unsigned()->setDefault(1);
            $t->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $t->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('mapping_id'); $t->addKey(['repository_id','enabled']); $t->addKey(['event','event_action']);
        });
    }

    private function createTemplate(): void
    {
        $this->schemaManager()->createTable('xf_wgh_template', function (Create $t)
        {
            $t->addColumn('template_id', 'int')->autoIncrement(); $t->addColumn('title', 'varchar', 150);
            $t->addColumn('event', 'varchar', 75)->setDefault('*'); $t->addColumn('title_template', 'text')->nullable();
            $t->addColumn('message_template', 'mediumtext')->nullable(); $t->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $t->addColumn('created_date', 'int')->unsigned()->setDefault(0); $t->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('template_id'); $t->addKey(['event','active']);
        });
    }

    private function createSyncObject(): void
    {
        $this->schemaManager()->createTable('xf_wgh_sync_object', function (Create $t)
        {
            $t->addColumn('sync_id', 'bigint')->autoIncrement(); $t->addColumn('mapping_id', 'int')->unsigned()->setDefault(0);
            $t->addColumn('repository_id', 'int')->unsigned()->setDefault(0); $t->addColumn('github_type', 'varchar', 50);
            $t->addColumn('github_id', 'varchar', 191); $t->addColumn('github_node_id', 'varchar', 191)->setDefault('');
            $t->addColumn('xf_content_type', 'varchar', 50); $t->addColumn('xf_content_id', 'bigint')->unsigned();
            $t->addColumn('sync_direction', 'varchar', 25)->setDefault('github_to_xf'); $t->addColumn('origin', 'varchar', 25)->setDefault('github');
            $t->addColumn('last_github_hash', 'char', 64)->setDefault(''); $t->addColumn('last_xf_hash', 'char', 64)->setDefault('');
            $t->addColumn('status', 'varchar', 25)->setDefault('active'); $t->addColumn('metadata', 'mediumblob')->nullable();
            $t->addColumn('last_sync_date', 'int')->unsigned()->setDefault(0); $t->addPrimaryKey('sync_id');
            $t->addUniqueKey(['mapping_id','github_type','github_id'], 'mapping_github_object'); $t->addKey(['xf_content_type','xf_content_id']); $t->addKey(['repository_id','status']);
        });
    }

    private function createDelivery(): void
    {
        $this->schemaManager()->createTable('xf_wgh_delivery', function (Create $t)
        {
            $t->addColumn('delivery_id', 'bigint')->autoIncrement(); $t->addColumn('github_delivery_guid', 'varchar', 100);
            $t->addColumn('github_event', 'varchar', 75); $t->addColumn('event_action', 'varchar', 75)->setDefault('');
            $t->addColumn('github_repository_id', 'bigint')->unsigned()->setDefault(0); $t->addColumn('payload_hash', 'char', 64);
            $t->addColumn('source_ref', 'varchar', 255)->setDefault(''); $t->addColumn('payload', 'mediumblob')->nullable();
            $t->addColumn('signature_valid', 'tinyint')->unsigned()->setDefault(0); $t->addColumn('status', 'varchar', 25)->setDefault('received');
            $t->addColumn('attempt_count', 'int')->unsigned()->setDefault(0); $t->addColumn('next_attempt_date', 'int')->unsigned()->setDefault(0);
            $t->addColumn('grouped_into_delivery_id', 'bigint')->unsigned()->setDefault(0); $t->addColumn('last_error', 'text')->nullable();
            $t->addColumn('received_date', 'int')->unsigned()->setDefault(0); $t->addColumn('processed_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('delivery_id'); $t->addUniqueKey('github_delivery_guid'); $t->addKey(['status','received_date']);
            $t->addKey(['github_repository_id','github_event']); $t->addKey(['github_repository_id','github_event','source_ref'], 'repo_event_ref');
        });
    }

    private function createUserMapping(): void
    {
        $this->schemaManager()->createTable('xf_wgh_user_mapping', function (Create $t)
        {
            $t->addColumn('user_mapping_id', 'int')->autoIncrement(); $t->addColumn('connection_id', 'int')->unsigned()->setDefault(0);
            $t->addColumn('repository_id', 'int')->unsigned()->setDefault(0); $t->addColumn('github_login', 'varchar', 100);
            $t->addColumn('xf_user_id', 'int')->unsigned(); $t->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $t->addColumn('created_date', 'int')->unsigned()->setDefault(0); $t->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('user_mapping_id'); $t->addUniqueKey(['connection_id','repository_id','github_login'], 'scope_login'); $t->addKey(['xf_user_id','active']);
        });
    }

    private function createConflict(): void
    {
        $this->schemaManager()->createTable('xf_wgh_conflict', function (Create $t)
        {
            $t->addColumn('conflict_id', 'bigint')->autoIncrement(); $t->addColumn('mapping_id', 'int')->unsigned(); $t->addColumn('repository_id', 'int')->unsigned();
            $t->addColumn('sync_id', 'bigint')->unsigned()->setDefault(0); $t->addColumn('github_type', 'varchar', 50)->setDefault(''); $t->addColumn('github_id', 'varchar', 191)->setDefault('');
            $t->addColumn('xf_content_type', 'varchar', 50)->setDefault(''); $t->addColumn('xf_content_id', 'bigint')->unsigned()->setDefault(0);
            $t->addColumn('github_hash', 'char', 64)->setDefault(''); $t->addColumn('xf_hash', 'char', 64)->setDefault('');
            $t->addColumn('action_payload', 'mediumblob')->nullable(); $t->addColumn('status', 'enum')->values(['pending','resolved','dismissed'])->setDefault('pending');
            $t->addColumn('resolution', 'varchar', 25)->setDefault(''); $t->addColumn('created_date', 'int')->unsigned()->setDefault(0); $t->addColumn('resolved_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('conflict_id'); $t->addKey(['status','created_date']); $t->addKey(['mapping_id','status']); $t->addKey(['sync_id','status']);
        });
    }

    private function createXfrmRelease(bool $v08): void
    {
        $this->schemaManager()->createTable('xf_wgh_xfrm_release', function (Create $t) use ($v08)
        {
            $t->addColumn('xfrm_release_id', 'bigint')->autoIncrement(); $t->addColumn('mapping_id', 'int')->unsigned(); $t->addColumn('repository_id', 'int')->unsigned();
            $t->addColumn('github_release_id', 'bigint')->unsigned(); $t->addColumn('resource_id', 'int')->unsigned();
            $t->addColumn('resource_version_id', 'bigint')->unsigned()->setDefault(0); $t->addColumn('resource_update_id', 'bigint')->unsigned()->setDefault(0);
            $t->addColumn('version_string', 'varchar', 100)->setDefault(''); $t->addColumn('download_url', 'varchar', 1000)->setDefault('');
            $t->addColumn('release_hash', 'char', 64)->setDefault(''); $t->addColumn('status', 'varchar', 25)->setDefault('pending'); $t->addColumn('last_error', 'varchar', 1000)->setDefault('');
            if ($v08)
            {
                $t->addColumn('retry_count', 'int')->unsigned()->setDefault(0); $t->addColumn('last_attempt_date', 'int')->unsigned()->setDefault(0);
                $t->addColumn('last_success_date', 'int')->unsigned()->setDefault(0); $t->addColumn('last_reconcile_date', 'int')->unsigned()->setDefault(0);
                $t->addColumn('remote_state', 'varchar', 25)->setDefault('unknown');
            }
            $t->addColumn('created_date', 'int')->unsigned()->setDefault(0); $t->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $t->addPrimaryKey('xfrm_release_id'); $t->addUniqueKey(['mapping_id','github_release_id'], 'mapping_release');
            $t->addKey(['resource_id','status']); $t->addKey(['repository_id','updated_date']); if ($v08) $t->addKey(['status','last_attempt_date']);
        });
    }

    private function createXfrmHistory(): void
    {
        $this->schemaManager()->createTable('xf_wgh_xfrm_history', function (Create $t)
        {
            $t->addColumn('xfrm_history_id', 'bigint')->autoIncrement(); $t->addColumn('xfrm_release_id', 'bigint')->unsigned();
            $t->addColumn('mapping_id', 'int')->unsigned(); $t->addColumn('repository_id', 'int')->unsigned();
            $t->addColumn('action', 'varchar', 50)->setDefault(''); $t->addColumn('status', 'varchar', 25)->setDefault('');
            $t->addColumn('message', 'varchar', 1000)->setDefault(''); $t->addColumn('resource_version_id', 'bigint')->unsigned()->setDefault(0);
            $t->addColumn('resource_update_id', 'bigint')->unsigned()->setDefault(0); $t->addColumn('release_hash', 'char', 64)->setDefault('');
            $t->addColumn('created_date', 'int')->unsigned()->setDefault(0); $t->addPrimaryKey('xfrm_history_id');
            $t->addKey(['xfrm_release_id','created_date']); $t->addKey(['status','created_date']);
        });
    }
}
