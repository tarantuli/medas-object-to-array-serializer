<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{
    Attributes\Entrypoint,
    Attributes\RequiredButUnused,
    Attributes\Service,
    Interfaces\Serializer,
    Interfaces\Type
};

#[Service, Entrypoint]
readonly class ObjectToArraySerializer implements Serializer
{
    public function __construct(
        private ArrayToObjectCaster $arrayToObjectCaster,
        private ObjectToArrayCaster $objectToArrayCaster,
    )
    {
    }

    /**
     * Transforms the given object into an array of values.
     */
    public function serialize(mixed $value): array
    {
        if (!is_object($value)) {
            throw new Exceptions\ValueMustBeObject($value);
        }

        return $this->objectToArrayCaster->cast($value);
    }

    /**
     * Transforms the given array of values back into an object of the given class name.
     */
    public function unserialize(
        mixed       $value,

        #[RequiredButUnused]
        Type|null   $type = null,
        string|null $class = null
    ): object
    {
        if (!is_array($value)) {
            throw new Exceptions\ValueMustBeArray($value);
        }

        if ($class === null || !class_exists($class)) {
            throw new Exceptions\ClassNameMustBeValidAndExisting($class);
        }

        return $this->arrayToObjectCaster->cast($value, $class);
    }
}
