<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;

final class Capability
{
    public function available(): bool
    {
        return class_exists('XFRM\\Entity\\ResourceItem')
            && class_exists('XFRM\\Entity\\ResourceVersion');
    }

    public function assertAvailable(): void
    {
        if (!$this->available())
        {
            throw new RuntimeException('XenForo Resource Manager (XFRM) 2.3+ is not installed or not loaded.');
        }
    }

    public function resource(int $resourceId)
    {
        $this->assertAvailable();
        if ($resourceId <= 0)
        {
            throw new RuntimeException('XFRM resource ID is not configured.');
        }

        $resource = \XF::em()->find('XFRM:ResourceItem', $resourceId);
        if (!$resource)
        {
            throw new RuntimeException('XFRM resource #' . $resourceId . ' could not be found.');
        }
        return $resource;
    }
}
