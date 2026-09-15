<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class XfrmRelease extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_xfrm_release';
        $structure->shortName = 'Warext\\GitHubSync:XfrmRelease';
        $structure->primaryKey = 'xfrm_release_id';
        $structure->columns = [
            'xfrm_release_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'mapping_id' => ['type' => self::UINT, 'required' => true],
            'repository_id' => ['type' => self::UINT, 'required' => true],
            'github_release_id' => ['type' => self::UINT, 'required' => true],
            'resource_id' => ['type' => self::UINT, 'required' => true],
            'resource_version_id' => ['type' => self::UINT, 'default' => 0],
            'resource_update_id' => ['type' => self::UINT, 'default' => 0],
            'version_string' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'download_url' => ['type' => self::STR, 'maxLength' => 1000, 'default' => ''],
            'release_hash' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'status' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'pending'],
            'last_error' => ['type' => self::STR, 'maxLength' => 1000, 'default' => ''],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
