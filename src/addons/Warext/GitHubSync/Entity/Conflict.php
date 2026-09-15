<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Conflict extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_conflict';
        $structure->shortName = 'Warext\\GitHubSync:Conflict';
        $structure->primaryKey = 'conflict_id';
        $structure->columns = [
            'conflict_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'mapping_id' => ['type' => self::UINT, 'required' => true],
            'repository_id' => ['type' => self::UINT, 'required' => true],
            'sync_id' => ['type' => self::UINT, 'default' => 0],
            'github_type' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'github_id' => ['type' => self::STR, 'maxLength' => 191, 'default' => ''],
            'xf_content_type' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'xf_content_id' => ['type' => self::UINT, 'default' => 0],
            'github_hash' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'xf_hash' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'action_payload' => ['type' => self::JSON_ARRAY, 'default' => []],
            'status' => ['type' => self::STR, 'allowedValues' => ['pending', 'resolved', 'dismissed'], 'default' => 'pending'],
            'resolution' => ['type' => self::STR, 'maxLength' => 25, 'default' => ''],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'resolved_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
