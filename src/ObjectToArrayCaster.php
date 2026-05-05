<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{
    Attributes\Entrypoint,
    Attributes\Handler,
    Attributes\ObjectToArrayHandler,
    Attributes\Service,
    Interfaces\ObjectToArrayHandler as ObjectToArrayHandlerInterface,
    Interfaces\PropertyHandler
};

#[Service, Entrypoint]
readonly class ObjectToArrayCaster
{
    public const string HANDLER_KEY_PREFIX = '__handler:';

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

        if ($objectHandlerAttribute = attribute(ObjectToArrayHandler::class, $reflectionClass)) {
            // There is a custom handler for this object, so we use it
            /** @var ObjectToArrayHandlerInterface $handler */
            $handler = \service($objectHandlerAttribute->className);

            // They key is a special value which is extremely unlikely to occur in other serialization data, that will
            // be used to identify the handler
            return [self::HANDLER_KEY_PREFIX . $objectHandlerAttribute->className => $handler->toArray($object)];
        }

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
