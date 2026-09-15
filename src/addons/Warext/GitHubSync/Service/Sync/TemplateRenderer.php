<?php

namespace Warext\GitHubSync\Service\Sync;

final class TemplateRenderer
{
    public function render(string $template, array $vars): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function(array $match) use ($vars)
        {
            $value = $this->getPath($vars, $match[1]);
            if (is_scalar($value) || $value === null)
            {
                return (string)$value;
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }, $template) ?? $template;
    }

    private function getPath(array $vars, string $path): mixed
    {
        $value = $vars;
        foreach (explode('.', $path) as $part)
        {
            if (!is_array($value) || !array_key_exists($part, $value))
            {
                return '';
            }
            $value = $value[$part];
        }
        return $value;
    }
}
