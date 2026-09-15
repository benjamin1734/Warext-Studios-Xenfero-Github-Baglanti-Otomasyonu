<?php

namespace Warext\GitHubSync\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class HealthAlert extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_wgh_health_alert';
        $structure->shortName = 'Warext\\GitHubSync:HealthAlert';
        $structure->primaryKey = 'health_alert_id';
        $structure->columns = [
            'health_alert_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'fingerprint' => ['type' => self::STR, 'maxLength' => 64, 'required' => true],
            'severity' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'degraded'],
            'status' => ['type' => self::STR, 'maxLength' => 25, 'default' => 'open'],
            'message' => ['type' => self::STR, 'maxLength' => 2000, 'default' => ''],
            'occurrence_count' => ['type' => self::UINT, 'default' => 1],
            'first_seen_date' => ['type' => self::UINT, 'default' => 0],
            'last_seen_date' => ['type' => self::UINT, 'default' => 0],
            'resolved_date' => ['type' => self::UINT, 'default' => 0]
        ];
        return $structure;
    }
}
