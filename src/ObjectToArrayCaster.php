<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\Attributes\Service;

#[Service]
class ObjectToArrayCaster
{
    public function cast(object $value): array
    {
        $value = $this->castToArray($value);

        // Recursively cast child values to arrays as well
        do {
            $foundObject = false;

            array_walk_recursive($value, function (&$nodeValue) use (&$foundObject) {
                if (!is_object($nodeValue)) {
                    return;
                }

                $foundObject = true;
                $nodeValue = $this->castToArray($nodeValue);
            });
        } while ($foundObject);

        return $value;
    }

    private function castToArray(object $value): array
    {
        return $this->normalizePrivatePropertyNames((array) $value);
    }

    private function normalizePrivatePropertyNames(array $sourceArray): array
    {
        $result = [];

        foreach ($sourceArray as $key => $basicValue) {
            if (preg_match('/^\0.+\0(.+)$/', (string) $key, $match)) {
                $key = $match[1];
            }

            $result[$key] = $basicValue;
        }

        return $result;
    }
}
