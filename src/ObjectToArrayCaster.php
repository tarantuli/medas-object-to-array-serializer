<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\Attributes\Service;

#[Service]
readonly class ObjectToArrayCaster
{
    public function __construct(
        private SerializeToClassName\ClassManager $serializeToClassNameManager,
    )
    {
    }

    public function cast(object $value): array
    {
        $value = $this->castToArray($value);

        // Recursively cast child values to arrays as well
        do {
            $foundObject = false;

            array_walk_recursive($value, function (&$nodeValue) use (&$foundObject) {
                if (!is_object($nodeValue) || $nodeValue instanceof \Closure) {
                    return;
                }

                $foundObject = true;

                if ($this->serializeToClassNameManager->shouldSerializeToClassName($nodeValue::class)) {
                    $nodeValue = $nodeValue::class;
                }
                else {
                    $nodeValue = $this->castToArray($nodeValue);
                }
            }

            );
        } while ($foundObject);

        return $value;
    }

    private function castToArray(object $object): array
    {
        $values = [];

        foreach ((new \ReflectionClass($object))->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isInitialized($object)) {
                continue;
            }

            if ($reflectionProperty->getAttributes(DontSerializeMe::class)) {
                continue;
            }

            $values[$reflectionProperty->name] = $reflectionProperty->getValue($object);
        }

        return $values;
    }
}
