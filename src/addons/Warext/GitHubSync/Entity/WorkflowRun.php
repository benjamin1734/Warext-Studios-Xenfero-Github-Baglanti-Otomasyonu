<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class WorkflowRun extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_workflow_run';
        $structure->shortName = 'Warext\\GitHubSync:WorkflowRun';
        $structure->primaryKey = 'workflow_run_id';
        $structure->columns = [
            'workflow_run_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'repository_id' => ['type' => self::UINT, 'required' => true],
            'github_run_id' => ['type' => self::UINT, 'required' => true],
            'github_workflow_id' => ['type' => self::UINT, 'default' => 0],
            'workflow_name' => ['type' => self::STR, 'maxLength' => 191, 'default' => ''],
            'run_number' => ['type' => self::UINT, 'default' => 0],
            'event' => ['type' => self::STR, 'maxLength' => 75, 'default' => ''],
            'branch' => ['type' => self::STR, 'maxLength' => 191, 'default' => ''],
            'head_sha' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'status' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'conclusion' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'actor_login' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'html_url' => ['type' => self::STR, 'maxLength' => 1000, 'default' => ''],
            'github_created_date' => ['type' => self::UINT, 'default' => 0],
            'github_updated_date' => ['type' => self::UINT, 'default' => 0],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
