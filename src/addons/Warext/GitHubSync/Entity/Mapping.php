<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Mapping extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_mapping';
        $structure->shortName = 'Warext\\GitHubSync:Mapping';
        $structure->primaryKey = 'mapping_id';
        $structure->columns = [
            'mapping_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'repository_id' => ['type' => self::UINT, 'default' => 0],
            'title' => ['type' => self::STR, 'maxLength' => 150, 'required' => true],
            'direction' => ['type' => self::STR, 'allowedValues' => ['github_to_xf', 'xf_to_github', 'bidirectional'], 'default' => 'github_to_xf'],
            'event' => ['type' => self::STR, 'maxLength' => 75, 'default' => '*'],
            'event_action' => ['type' => self::STR, 'maxLength' => 75, 'default' => '*'],
            'source_config' => ['type' => self::JSON_ARRAY, 'default' => []],
            'target_config' => ['type' => self::JSON_ARRAY, 'default' => []],
            'filter_config' => ['type' => self::JSON_ARRAY, 'default' => []],
            'conflict_strategy' => ['type' => self::STR, 'maxLength' => 30, 'default' => 'manual'],
            'priority' => ['type' => self::INT, 'default' => 100],
            'enabled' => ['type' => self::BOOL, 'default' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
