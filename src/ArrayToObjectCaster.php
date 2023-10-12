<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\Attributes\Service;
use Medas\PhpClassAnalysis\ClassAnalyser;

#[Service]
readonly class ArrayToObjectCaster
{
    public function __construct(
        private ClassAnalyser $classAnalyser,
    )
    {
    }

    public function cast(array $values, string $className): object
    {
        $reflectionClass = new \ReflectionClass($className);
        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($values as $propertyName => $value) {
            $reflectionProperty = $reflectionClass->getProperty($propertyName);
            $types = propertyTypes($reflectionProperty);

            if ($reflectionProperty->getType()?->allowsNull() && $value === null) {
                // Do nothing
            }
            elseif (count($types) === 1) {
                $typeName = $types[0]->getName();

                if ($typeName === 'array' && is_iterable($value)) {
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
        if ($typeName === 'mixed') {
            return;
        }

        if (get_debug_type($value) === $typeName) {
            // The value already has the right type
            return;
        }

        if (is_array($value) && class_exists($typeName)) {
            if (enum_exists($typeName)) {
                $value = $this->getEnumValue($value, new \ReflectionEnum($typeName));
            }
            else {
                $value = $this->cast($value, $typeName);
            }
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

        if (!preg_match('/@var\s+([\w\\\]+)\[]/', $doccomment, $match)) {
            throw new Exceptions\ArraysMustSpecifyContentType($reflectionProperty);
        }

        $childClass = $this->referenceToFqcn($match[1], $reflectionClass);

        foreach ($value as &$child) {
            $this->checkValueType($child, $childClass);
        }
    }

    private function referenceToFqcn(string $childClass, \ReflectionClass $reflectionClass): string
    {
        if (str_starts_with($childClass, '\\')) {
            return $childClass;
        }

        $analysis = $this->classAnalyser->analyseClass($reflectionClass);
        $fqcn = $analysis->resolveImport($childClass);

        if ($fqcn === null) {
            $fqcn = ($analysis->namespace ? $analysis->namespace . '\\' : '') . $childClass;
        }

        return $fqcn;
    }

    private function getEnumValue(array $value, \ReflectionEnum $enum): mixed
    {
        return $enum->getCase($value['name'])->getValue();
    }
}
