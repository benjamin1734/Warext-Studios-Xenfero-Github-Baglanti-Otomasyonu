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
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $sm = $this->schemaManager();

        $sm->createTable('xf_wgh_connection', function (Create $table)
        {
            $table->addColumn('connection_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 100);
            $table->addColumn('provider', 'varchar', 25)->setDefault('github_app');
            $table->addColumn('app_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('installation_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('client_id', 'varchar', 100)->setDefault('');
            $table->addColumn('secret_ref', 'varchar', 191)->setDefault('');
            $table->addColumn('private_key_ref', 'varchar', 191)->setDefault('');
            $table->addColumn('account_login', 'varchar', 100)->setDefault('');
            $table->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('connection_id');
            $table->addKey('active');
        });

        $sm->createTable('xf_wgh_repository', function (Create $table)
        {
            $table->addColumn('repository_id', 'int')->autoIncrement();
            $table->addColumn('connection_id', 'int')->unsigned();
            $table->addColumn('github_repository_id', 'bigint')->unsigned();
            $table->addColumn('full_name', 'varchar', 191);
            $table->addColumn('owner_name', 'varchar', 100)->setDefault('');
            $table->addColumn('repo_name', 'varchar', 100)->setDefault('');
            $table->addColumn('default_branch', 'varchar', 100)->setDefault('main');
            $table->addColumn('html_url', 'varchar', 500)->setDefault('');
            $table->addColumn('is_private', 'tinyint')->unsigned()->setDefault(0);
            $table->addColumn('is_archived', 'tinyint')->unsigned()->setDefault(0);
            $table->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('repository_id');
            $table->addUniqueKey('github_repository_id');
            $table->addKey(['connection_id', 'active']);
        });

        $sm->createTable('xf_wgh_mapping', function (Create $table)
        {
            $table->addColumn('mapping_id', 'int')->autoIncrement();
            $table->addColumn('repository_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('title', 'varchar', 150);
            $table->addColumn('direction', 'enum')->values(['github_to_xf', 'xf_to_github', 'bidirectional'])->setDefault('github_to_xf');
            $table->addColumn('event', 'varchar', 75)->setDefault('*');
            $table->addColumn('event_action', 'varchar', 75)->setDefault('*');
            $table->addColumn('source_config', 'mediumblob')->nullable();
            $table->addColumn('target_config', 'mediumblob')->nullable();
            $table->addColumn('filter_config', 'mediumblob')->nullable();
            $table->addColumn('conflict_strategy', 'varchar', 30)->setDefault('manual');
            $table->addColumn('priority', 'int')->setDefault(100);
            $table->addColumn('enabled', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('mapping_id');
            $table->addKey(['repository_id', 'enabled']);
            $table->addKey(['event', 'event_action']);
        });

        $sm->createTable('xf_wgh_template', function (Create $table)
        {
            $table->addColumn('template_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 150);
            $table->addColumn('event', 'varchar', 75)->setDefault('*');
            $table->addColumn('title_template', 'text')->nullable();
            $table->addColumn('message_template', 'mediumtext')->nullable();
            $table->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('template_id');
            $table->addKey(['event', 'active']);
        });

        $sm->createTable('xf_wgh_sync_object', function (Create $table)
        {
            $table->addColumn('sync_id', 'bigint')->autoIncrement();
            $table->addColumn('mapping_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('repository_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('github_type', 'varchar', 50);
            $table->addColumn('github_id', 'varchar', 191);
            $table->addColumn('github_node_id', 'varchar', 191)->setDefault('');
            $table->addColumn('xf_content_type', 'varchar', 50);
            $table->addColumn('xf_content_id', 'bigint')->unsigned();
            $table->addColumn('sync_direction', 'varchar', 25)->setDefault('github_to_xf');
            $table->addColumn('origin', 'varchar', 25)->setDefault('github');
            $table->addColumn('last_github_hash', 'char', 64)->setDefault('');
            $table->addColumn('last_xf_hash', 'char', 64)->setDefault('');
            $table->addColumn('status', 'varchar', 25)->setDefault('active');
            $table->addColumn('metadata', 'mediumblob')->nullable();
            $table->addColumn('last_sync_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('sync_id');
            $table->addUniqueKey(['mapping_id', 'github_type', 'github_id'], 'mapping_github_object');
            $table->addKey(['xf_content_type', 'xf_content_id']);
            $table->addKey(['repository_id', 'status']);
        });

        $sm->createTable('xf_wgh_delivery', function (Create $table)
        {
            $table->addColumn('delivery_id', 'bigint')->autoIncrement();
            $table->addColumn('github_delivery_guid', 'varchar', 100);
            $table->addColumn('github_event', 'varchar', 75);
            $table->addColumn('event_action', 'varchar', 75)->setDefault('');
            $table->addColumn('github_repository_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('payload_hash', 'char', 64);
            $table->addColumn('source_ref', 'varchar', 255)->setDefault('');
            $table->addColumn('payload', 'mediumblob')->nullable();
            $table->addColumn('signature_valid', 'tinyint')->unsigned()->setDefault(0);
            $table->addColumn('status', 'varchar', 25)->setDefault('received');
            $table->addColumn('attempt_count', 'int')->unsigned()->setDefault(0);
            $table->addColumn('next_attempt_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('grouped_into_delivery_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('last_error', 'text')->nullable();
            $table->addColumn('received_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('processed_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('delivery_id');
            $table->addUniqueKey('github_delivery_guid');
            $table->addKey(['status', 'received_date']);
            $table->addKey(['github_repository_id', 'github_event']);
            $table->addKey(['github_repository_id', 'github_event', 'source_ref'], 'repo_event_ref');
        });

        $sm->createTable('xf_wgh_user_mapping', function (Create $table)
        {
            $table->addColumn('user_mapping_id', 'int')->autoIncrement();
            $table->addColumn('connection_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('repository_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('github_login', 'varchar', 100);
            $table->addColumn('xf_user_id', 'int')->unsigned();
            $table->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('user_mapping_id');
            $table->addUniqueKey(['connection_id', 'repository_id', 'github_login'], 'scope_login');
            $table->addKey(['xf_user_id', 'active']);
        });

        $sm->createTable('xf_wgh_conflict', function (Create $table)
        {
            $table->addColumn('conflict_id', 'bigint')->autoIncrement();
            $table->addColumn('mapping_id', 'int')->unsigned();
            $table->addColumn('repository_id', 'int')->unsigned();
            $table->addColumn('sync_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('github_type', 'varchar', 50)->setDefault('');
            $table->addColumn('github_id', 'varchar', 191)->setDefault('');
            $table->addColumn('xf_content_type', 'varchar', 50)->setDefault('');
            $table->addColumn('xf_content_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('github_hash', 'char', 64)->setDefault('');
            $table->addColumn('xf_hash', 'char', 64)->setDefault('');
            $table->addColumn('action_payload', 'mediumblob')->nullable();
            $table->addColumn('status', 'enum')->values(['pending', 'resolved', 'dismissed'])->setDefault('pending');
            $table->addColumn('resolution', 'varchar', 25)->setDefault('');
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('resolved_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('conflict_id');
            $table->addKey(['status', 'created_date']);
            $table->addKey(['mapping_id', 'status']);
            $table->addKey(['sync_id', 'status']);
        });

    }

    public function upgrade2000020Step1(): void
    {
        $this->schemaManager()->alterTable('xf_wgh_connection', function (Alter $table)
        {
            $table->addColumn('private_key_ref', 'varchar', 191)->setDefault('');
            $table->addColumn('account_login', 'varchar', 100)->setDefault('');
        });
    }

    public function upgrade3000030Step1(): void
    {
        $this->schemaManager()->alterTable('xf_wgh_delivery', function (Alter $table)
        {
            $table->addColumn('source_ref', 'varchar', 255)->setDefault('')->after('payload_hash');
            $table->addColumn('next_attempt_date', 'int')->unsigned()->setDefault(0)->after('attempt_count');
            $table->addColumn('grouped_into_delivery_id', 'bigint')->unsigned()->setDefault(0)->after('next_attempt_date');
            $table->addKey(['github_repository_id', 'github_event', 'source_ref'], 'repo_event_ref');
        });
    }

    public function upgrade3000030Step2(): void
    {
        $this->schemaManager()->createTable('xf_wgh_template', function (Create $table)
        {
            $table->addColumn('template_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 150);
            $table->addColumn('event', 'varchar', 75)->setDefault('*');
            $table->addColumn('title_template', 'text')->nullable();
            $table->addColumn('message_template', 'mediumtext')->nullable();
            $table->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('template_id');
            $table->addKey(['event', 'active']);
        });
    }


    public function upgrade5000050Step1(): void
    {
        $this->schemaManager()->createTable('xf_wgh_user_mapping', function (Create $table)
        {
            $table->addColumn('user_mapping_id', 'int')->autoIncrement();
            $table->addColumn('connection_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('repository_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('github_login', 'varchar', 100);
            $table->addColumn('xf_user_id', 'int')->unsigned();
            $table->addColumn('active', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('user_mapping_id');
            $table->addUniqueKey(['connection_id', 'repository_id', 'github_login'], 'scope_login');
            $table->addKey(['xf_user_id', 'active']);
        });
    }

    public function upgrade5000050Step2(): void
    {
        $this->schemaManager()->createTable('xf_wgh_conflict', function (Create $table)
        {
            $table->addColumn('conflict_id', 'bigint')->autoIncrement();
            $table->addColumn('mapping_id', 'int')->unsigned();
            $table->addColumn('repository_id', 'int')->unsigned();
            $table->addColumn('sync_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('github_type', 'varchar', 50)->setDefault('');
            $table->addColumn('github_id', 'varchar', 191)->setDefault('');
            $table->addColumn('xf_content_type', 'varchar', 50)->setDefault('');
            $table->addColumn('xf_content_id', 'bigint')->unsigned()->setDefault(0);
            $table->addColumn('github_hash', 'char', 64)->setDefault('');
            $table->addColumn('xf_hash', 'char', 64)->setDefault('');
            $table->addColumn('action_payload', 'mediumblob')->nullable();
            $table->addColumn('status', 'enum')->values(['pending', 'resolved', 'dismissed'])->setDefault('pending');
            $table->addColumn('resolution', 'varchar', 25)->setDefault('');
            $table->addColumn('created_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('resolved_date', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('conflict_id');
            $table->addKey(['status', 'created_date']);
            $table->addKey(['mapping_id', 'status']);
            $table->addKey(['sync_id', 'status']);
        });
    }

    public function uninstallStep1(): void
    {
        $sm = $this->schemaManager();
        foreach ([
            'xf_wgh_conflict',
            'xf_wgh_user_mapping',
            'xf_wgh_delivery',
            'xf_wgh_sync_object',
            'xf_wgh_template',
            'xf_wgh_mapping',
            'xf_wgh_repository',
            'xf_wgh_connection'
        ] as $table)
        {
            $sm->dropTable($table);
        }
    }
}
