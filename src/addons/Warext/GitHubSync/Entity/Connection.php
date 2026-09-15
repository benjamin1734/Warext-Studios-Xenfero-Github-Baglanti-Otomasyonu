<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Connection extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_connection';
        $structure->shortName = 'Warext\\GitHubSync:Connection';
        $structure->primaryKey = 'connection_id';
        $structure->columns = [
            'connection_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'provider' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'github_app'],
            'app_id' => ['type' => self::UINT, 'default' => 0],
            'installation_id' => ['type' => self::UINT, 'default' => 0],
            'client_id' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'secret_ref' => ['type' => self::STR, 'maxLength' => 191, 'default' => ''],
            'private_key_ref' => ['type' => self::STR, 'maxLength' => 191, 'default' => ''],
            'account_login' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'active' => ['type' => self::BOOL, 'default' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
