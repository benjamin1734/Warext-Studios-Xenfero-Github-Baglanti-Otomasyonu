<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Repository extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_repository';
        $structure->shortName = 'Warext\\GitHubSync:Repository';
        $structure->primaryKey = 'repository_id';
        $structure->columns = [
            'repository_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'connection_id' => ['type' => self::UINT, 'required' => true],
            'github_repository_id' => ['type' => self::UINT, 'required' => true],
            'full_name' => ['type' => self::STR, 'maxLength' => 191, 'required' => true],
            'owner_name' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'repo_name' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'default_branch' => ['type' => self::STR, 'maxLength' => 100, 'default' => 'main'],
            'html_url' => ['type' => self::STR, 'maxLength' => 500, 'default' => ''],
            'is_private' => ['type' => self::BOOL, 'default' => false],
            'is_archived' => ['type' => self::BOOL, 'default' => false],
            'active' => ['type' => self::BOOL, 'default' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
