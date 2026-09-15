<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class SyncObject extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_sync_object';
        $structure->shortName = 'Warext\\GitHubSync:SyncObject';
        $structure->primaryKey = 'sync_id';
        $structure->columns = [
            'sync_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'mapping_id' => ['type' => self::UINT, 'default' => 0],
            'repository_id' => ['type' => self::UINT, 'default' => 0],
            'github_type' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'github_id' => ['type' => self::STR, 'maxLength' => 191, 'required' => true],
            'github_node_id' => ['type' => self::STR, 'maxLength' => 191, 'default' => ''],
            'xf_content_type' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'xf_content_id' => ['type' => self::UINT, 'required' => true],
            'sync_direction' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'github_to_xf'],
            'origin' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'github'],
            'last_github_hash' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'last_xf_hash' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'status' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'active'],
            'metadata' => ['type' => self::JSON_ARRAY, 'default' => []],
            'last_sync_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
