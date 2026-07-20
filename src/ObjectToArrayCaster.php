<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{
    Attributes\Entrypoint,
    Attributes\Service,
    Interfaces\ObjectToArrayCaster as ObjectToArrayCasterInterface,
    Interfaces\ObjectToArrayHandler as ObjectToArrayHandlerInterface,
    Interfaces\PropertyHandler
};

#[Service, Entrypoint]
readonly class ObjectToArrayCaster implements ObjectToArrayCasterInterface
{
    public const string HANDLER_KEY_PREFIX = '__handler:';

    public function __construct(
        private ClassMetadata\ClassMetadataCache  $classMetadataCache,
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

        return $this->processArray($this->castToArray($value), $castObjects);
    }

    private function processArray(array $array, array &$castObjects): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $this->processValue($value, $castObjects);
        }

        return $array;
    }

    private function processValue(mixed $value, array &$castObjects): mixed
    {
        if (is_array($value)) {
            return $this->processArray($value, $castObjects);
        }

        if (!is_object($value) || $value instanceof \Closure) {
            return $value;
        }

        $objectId = spl_object_id($value);

        if (array_key_exists($objectId, $castObjects)) {
            return ["recursion" => $castObjects[$objectId]];
        }

        $castObjects[$objectId] = count($castObjects);

        if ($this->serializeToClassNameManager->shouldSerializeToClassName($value::class)) {
            return $value::class;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return $this->processArray($this->castToArray($value), $castObjects);
    }

    private function castToArray(object $object): array
    {
        $meta = $this->classMetadataCache->get($object::class);

        if ($meta->objectHandler !== null) {
            /** @var ObjectToArrayHandlerInterface $handler */
            return [self::HANDLER_KEY_PREFIX . $meta->objectHandlerClass => $meta->objectHandler->toArray($object)];
        }

        $values = [];

        foreach ($meta->properties as $propMeta) {
            if (!$propMeta->reflection->isInitialized($object)) {
                continue;
            }

            $value = $propMeta->reflection->getValue($object);

            if ($value instanceof \Closure) {
                continue;
            }

            if ($propMeta->handler !== null) {
                /** @var PropertyHandler $handler */
                $value = $propMeta->handler->serialize($value);
            }

            if ($meta->dontSerializeEmptyValues && empty($value) && $value !== '0' && $value !== '') {
                continue;
            }

            $values[$propMeta->name] = $value;
        }

        return $values;
    }
}
