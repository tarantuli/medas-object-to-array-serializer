<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{Attributes\Service, Interfaces\Serializer, Interfaces\Type};

#[Service]
readonly class ObjectToArraySerializer implements Serializer
{
    public function __construct(
        private ObjectToArrayCaster $objectToArrayCaster,
        private ArrayToObjectCaster $arrayToObjectCaster,
    )
    {
    }

    public function serialize(mixed $value): array
    {
        if (!is_object($value)) {
            throw new Exceptions\ValueMustBeObject($value);
        }

        return $this->objectToArrayCaster->cast($value);
    }

    public function unserialize(mixed $value, Type $type = null, string $class = null): object
    {
        if (!is_array($value)) {
            throw new Exceptions\ValueMustBeArray($value);
        }

        if ($class === null || !class_exists($class)) {
            throw new Exceptions\ClassNameMustBeString($class);
        }

        return $this->arrayToObjectCaster->cast($value, $class);
    }
}
