<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\Attributes\Service;

#[Service]
class ArrayToObjectCaster
{
    public function cast(array $values, string $className): object
    {
        $reflectionClass = new \ReflectionClass($className);
        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($values as $propertyName => $value) {
            $reflectionProperty = $reflectionClass->getProperty($propertyName);
            $types = parameterTypes($reflectionProperty);

            if (count($types) === 1) {
                $typeName = $types[0]->getName();

                if ($typeName === 'array') {
                    $this->castArrayMembers($reflectionProperty, $reflectionClass, $value);
                }

                $this->checkValueType($value, $typeName);
            }

            $reflectionProperty->setValue($object, $value);
        }

        return $object;
    }

    private function checkValueType(mixed &$value, string $typeName): void
    {
        if (get_debug_type($value) === $typeName) {
            // The value already has the right type
            return;
        }

        if (is_array($value) && class_exists($typeName)) {
            $value = $this->cast($value, $typeName);
            return;
        }

        throw new Exceptions\CantCastValueToType($value, $typeName);
    }

    private function castArrayMembers(\ReflectionProperty $reflectionProperty, \ReflectionClass $reflectionClass, mixed &$value): void
    {
        $doccomment = $reflectionProperty->getDocComment();

        if ($doccomment === false) {
            throw new Exceptions\ArraysMustSpecifyContentType($reflectionProperty);
        }

        if (!preg_match('/@var\s+(\w+)\[]/', $doccomment, $match)) {
            throw new Exceptions\ArraysMustSpecifyContentType($reflectionProperty);
        }

        $childClass = $this->referenceToFqcn($match[1], $reflectionClass);

        foreach ($value as &$child) {
            $child = $this->cast($child, $childClass);
        }
    }

    private function referenceToFqcn(string $childClass, \ReflectionClass $reflectionClass): string
    {
        if (str_starts_with($childClass, '\\')) {
            return $childClass;
        }

        // TODO it does not handle use statements at all at the moment
        if ($reflectionClass->inNamespace()) {
            $childClass = '\\' . $reflectionClass->getNamespaceName() . '\\' . $childClass;
        }
        else {
            $childClass = '\\' . $childClass;
        }

        return $childClass;
    }
}
