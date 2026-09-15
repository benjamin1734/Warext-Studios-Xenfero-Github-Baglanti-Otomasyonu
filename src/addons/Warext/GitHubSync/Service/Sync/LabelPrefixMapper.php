<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Entity\Mapping;

final class LabelPrefixMapper
{
    public function prefixForLabels(Mapping $mapping, array $labels, int $fallback = 0): int
    {
        $map = $this->map($mapping);
        foreach ($labels as $label)
        {
            $name = is_array($label) ? (string)($label['name'] ?? '') : (string)$label;
            $key = mb_strtolower(trim($name));
            if ($key !== '' && isset($map[$key])) return (int)$map[$key];
        }
        return $fallback;
    }

    public function labelsForPrefix(Mapping $mapping, int $prefixId): array
    {
        if ($prefixId <= 0) return [];
        $labels = [];
        foreach ($this->map($mapping) as $label => $mappedPrefix)
        {
            if ((int)$mappedPrefix === $prefixId) $labels[] = $label;
        }
        return array_values(array_unique($labels));
    }

    private function map(Mapping $mapping): array
    {
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        $map = is_array($target['label_prefix_map'] ?? null) ? $target['label_prefix_map'] : [];
        $out = [];
        foreach ($map as $label => $prefix)
        {
            $label = mb_strtolower(trim((string)$label));
            $prefix = (int)$prefix;
            if ($label !== '' && $prefix > 0) $out[$label] = $prefix;
        }
        return $out;
    }
}
