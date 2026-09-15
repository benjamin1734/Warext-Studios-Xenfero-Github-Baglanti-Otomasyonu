<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class XfrmHistory extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_xfrm_history';
        $structure->shortName = 'Warext\\GitHubSync:XfrmHistory';
        $structure->primaryKey = 'xfrm_history_id';
        $structure->columns = [
            'xfrm_history_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'xfrm_release_id' => ['type' => self::UINT, 'required' => true],
            'mapping_id' => ['type' => self::UINT, 'required' => true],
            'repository_id' => ['type' => self::UINT, 'required' => true],
            'action' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'status' => ['type' => self::STR, 'maxLength' => 25, 'default' => ''],
            'message' => ['type' => self::STR, 'maxLength' => 1000, 'default' => ''],
            'resource_version_id' => ['type' => self::UINT, 'default' => 0],
            'resource_update_id' => ['type' => self::UINT, 'default' => 0],
            'release_hash' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'created_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
