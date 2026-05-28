<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\ClassMetadata;

use Medas\Core\Attributes\{Handler, ObjectToArrayHandler, Service};
use Medas\ObjectToArraySerializer\{DontSerializeEmptyValues, DontSerializeMe};

#[Service]
class ClassMetadataCache
{
    private static array $cache = [];

    public function get(string $className): ClassMeta
    {
        if (!isset(self::$cache[$className])) {
            self::$cache[$className] = $this->build($className);
        }

        return self::$cache[$className];
    }

    private function build(string $className): ClassMeta
    {
        $reflectionClass = new \ReflectionClass($className);
        $objectHandlerClass = null;
        $objectHandler = null;

        if ($attr = attribute(ObjectToArrayHandler::class, $reflectionClass)) {
            $objectHandlerClass = $attr->className;
            $objectHandler = \service($objectHandlerClass);
        }

        $dontSerializeEmptyValues = (bool) attribute(
            DontSerializeEmptyValues::class,
            $reflectionClass
        );

        $promoted = [];
        $nonPromoted = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->isStatic()) {
                continue;
            }

            if ($reflectionProperty->getAttributes(DontSerializeMe::class)) {
                continue;
            }

            if ($reflectionProperty->isPromoted()) {
                $promoted[] = $reflectionProperty;
            }
            else {
                $nonPromoted[] = $reflectionProperty;
            }
        }

        $properties = [];

        foreach (array_merge($promoted, $nonPromoted) as $reflectionProperty) {
            $handler = null;

            if ($handlerAttr = attribute(Handler::class, $reflectionProperty)) {
                $handler = \service($handlerAttr->className);
            }

            $properties[] = new PropertyMeta(
                name: $reflectionProperty->name,
                reflection: $reflectionProperty,
                handler: $handler,
            );
        }

        return new ClassMeta(
            objectHandlerClass: $objectHandlerClass,
            objectHandler: $objectHandler,
            dontSerializeEmptyValues: $dontSerializeEmptyValues,
            properties: $properties,
        );
    }
}
