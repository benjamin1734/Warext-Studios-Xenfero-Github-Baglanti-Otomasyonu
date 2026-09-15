<?php

namespace Warext\GitHubSync\Service\XFRM;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }
}
