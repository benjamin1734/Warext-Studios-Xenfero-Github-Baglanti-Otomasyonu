<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class UserMapping extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_user_mapping';
        $structure->shortName = 'Warext\\GitHubSync:UserMapping';
        $structure->primaryKey = 'user_mapping_id';
        $structure->columns = [
            'user_mapping_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'connection_id' => ['type' => self::UINT, 'default' => 0],
            'repository_id' => ['type' => self::UINT, 'default' => 0],
            'github_login' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'xf_user_id' => ['type' => self::UINT, 'required' => true],
            'active' => ['type' => self::BOOL, 'default' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
