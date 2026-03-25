<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{
    Attributes\Entrypoint,
    Attributes\Handler,
    Attributes\Service,
    Interfaces\PropertyHandler
};

#[Service, Entrypoint]
readonly class ObjectToArrayCaster
{
    public function __construct(
        private SerializeToClassName\ClassManager $serializeToClassNameManager,
    )
    {
    }

    public function cast(object $value, array $castObjects = []): array
    {
        $objectId = spl_object_id($value);

        if (array_key_exists($objectId, $castObjects)) {
            return ["recursion" => $castObjects[$objectId]];
        }

        $castObjects[$objectId] = count($castObjects);
        $value = $this->castToArray($value);

        // Recursively cast child values to arrays as well
        do {
            $foundObject = false;

            array_walk_recursive($value, function (&$nodeValue) use (&$foundObject, &$castObjects) {
                if (!is_object($nodeValue) || $nodeValue instanceof \Closure) {
                    return;
                }

                $objectId = spl_object_id($nodeValue);

                if (array_key_exists($objectId, $castObjects)) {
                    $nodeValue = ["recursion" => $castObjects[$objectId]];

                    return;
                }

                $castObjects[$objectId] = count($castObjects);
                $foundObject = true;

                if ($this->serializeToClassNameManager->shouldSerializeToClassName($nodeValue::class)) {
                    $nodeValue = $nodeValue::class;
                }
                elseif ($nodeValue instanceof \BackedEnum) {
                    $nodeValue = $nodeValue->value;
                }
                elseif ($nodeValue instanceof \UnitEnum) {
                    $nodeValue = $nodeValue->name;
                }
                else {
                    $nodeValue = $this->castToArray($nodeValue);
                }
            });
        } while ($foundObject);

        return $value;
    }

    private function castToArray(object $object): array
    {
        $reflectionClass = new \ReflectionClass($object);
        $dontSerializeEmptyValues = attribute(DontSerializeEmptyValues::class, $reflectionClass);
        $values = [];

        // This loop is to make sure that promoted properties are serialized first
        foreach ([true, false] as $promotionState) {
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                if ($reflectionProperty->isStatic()) {
                    continue;
                }

                if ($reflectionProperty->isPromoted() !== $promotionState) {
                    continue;
                }

                if (!$reflectionProperty->isInitialized($object)) {
                    continue;
                }

                if ($reflectionProperty->getAttributes(DontSerializeMe::class)) {
                    continue;
                }

                $value = $reflectionProperty->getValue($object);

                if ($value instanceof \Closure) {
                    continue;
                }

                if ($handlerAttribute = attribute(Handler::class, $reflectionProperty)) {
                    /** @var PropertyHandler $handler */
                    $handler = \service($handlerAttribute->className);
                    $value = $handler->serialize($value);
                }

                if ($dontSerializeEmptyValues && empty($value) && $value !== '0' && $value !== '') {
                    continue;
                }

                $values[$reflectionProperty->name] = $value;
            }
        }

        return $values;
    }
}
