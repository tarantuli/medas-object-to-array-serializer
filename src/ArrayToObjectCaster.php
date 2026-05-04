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
readonly class ArrayToObjectCaster
{
    public function __construct(
        private ArrayToObjectCaster\SettingsFactory $settingsFactory,
        private ValueCaster                         $valueCaster,
    )
    {
    }

    /**
     * The return value is an object of type `$className`.
     */
    /*
     * This is specified in PhpStorm in .phpstorm.meta.php
     */
    public function cast(
        array                             $values,
        string                            $className,
        ArrayToObjectCaster\Settings|null $settings = null
    ): object
    {
        $settings ??= $this->settingsFactory->create();
        $reflectionClass = new \ReflectionClass($className);

        if ($objectHandlerAttribute = attribute(ObjectToArrayHandler::class, $reflectionClass)) {
            /** @var ObjectToArrayHandlerInterface $handler */
            $handler = \service($objectHandlerAttribute->className);

            return $handler->toObject($values);
        }

        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($values as $propertyName => $value) {
            if (!$reflectionClass->hasProperty($propertyName)) {
                if ($settings->skipUnknownValues) {
                    continue;
                }

                throw new Exceptions\PropertyDoesNotExist($object, $propertyName);
            }

            $reflectionProperty = $reflectionClass->getProperty($propertyName);

            if ($handlerAttribute = attribute(Handler::class, $reflectionProperty)) {
                /** @var PropertyHandler $handler */
                $handler = \service($handlerAttribute->className);
                $value = $handler->unserialize($value);
            }
            else {
                $types = propertyTypes($reflectionProperty);

                if ($reflectionProperty->getType()?->allowsNull() && $value === null) {
                    // Do nothing
                }
                elseif (count($types) === 1) {
                    $typeName = $types[0]->getName();

                    if ($typeName === 'array' && is_array($value)) {
                        $this->valueCaster->castArrayMembers(
                            $this,
                            $reflectionProperty,
                            $reflectionClass,
                            $value
                        );
                    }

                    if (!$this->valueCaster->checkValueType($this, $value, $typeName)) {
                        throw new Exceptions\CantCastValueToType(
                            $className,
                            $propertyName,
                            $value,
                            $typeName
                        );
                    }
                }
                else {
                    throw new Exceptions\CastingToUnionTypesIsNotImplemented($reflectionProperty);
                }
            }

            if (!$reflectionProperty->isReadOnly() || !$reflectionProperty->isInitialized($object)) {
                $reflectionProperty->setValue($object, $value);
            }
        }

        return $object;
    }
}
