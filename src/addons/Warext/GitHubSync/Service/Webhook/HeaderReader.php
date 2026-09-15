<?php

namespace Warext\GitHubSync\Service\Webhook;

final class HeaderReader
{
    public function get(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$key]))
        {
            return trim((string)$_SERVER[$key]);
        }

        if (function_exists('getallheaders'))
        {
            foreach ((array)getallheaders() as $headerName => $value)
            {
                if (strcasecmp((string)$headerName, $name) === 0)
                {
                    return trim((string)$value);
                }
            }
        }

        return '';
    }
}
