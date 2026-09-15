<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Delivery extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_delivery';
        $structure->shortName = 'Warext\\GitHubSync:Delivery';
        $structure->primaryKey = 'delivery_id';
        $structure->columns = [
            'delivery_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'github_delivery_guid' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'github_event' => ['type' => self::STR, 'maxLength' => 75, 'required' => true],
            'event_action' => ['type' => self::STR, 'maxLength' => 75, 'default' => ''],
            'github_repository_id' => ['type' => self::UINT, 'default' => 0],
            'payload_hash' => ['type' => self::STR, 'maxLength' => 64, 'required' => true],
            'source_ref' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'payload' => ['type' => self::STR, 'default' => ''],
            'signature_valid' => ['type' => self::BOOL, 'default' => false],
            'status' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'received'],
            'attempt_count' => ['type' => self::UINT, 'default' => 0],
            'next_attempt_date' => ['type' => self::UINT, 'default' => 0],
            'grouped_into_delivery_id' => ['type' => self::UINT, 'default' => 0],
            'last_error' => ['type' => self::STR, 'default' => ''],
            'received_date' => ['type' => self::UINT, 'default' => 0],
            'processed_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
