<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Template extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_template';
        $structure->shortName = 'Warext\\GitHubSync:Template';
        $structure->primaryKey = 'template_id';
        $structure->columns = [
            'template_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'title' => ['type' => self::STR, 'maxLength' => 150, 'required' => true],
            'event' => ['type' => self::STR, 'maxLength' => 75, 'default' => '*'],
            'title_template' => ['type' => self::STR, 'default' => ''],
            'message_template' => ['type' => self::STR, 'default' => ''],
            'active' => ['type' => self::BOOL, 'default' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
