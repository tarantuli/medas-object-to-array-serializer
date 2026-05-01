<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\Attributes\Service;
use Medas\PhpClassAnalysis\{ClassAnalyser, PhpKeywords, ReferenceFinder\ReferenceResolver};

#[Service]
readonly class ValueCaster
{
    public function __construct(
        private ClassAnalyser                     $classAnalyser,
        private ReferenceResolver                 $referenceResolver,
        private SerializeToClassName\ClassManager $serializeToClassNameManager,
        private TemplateTypeFinder                $templateTypeFinder,
    )
    {
    }

    public function castArrayMembers(
        ArrayToObjectCaster $arrayToObjectCaster,
        \ReflectionProperty $reflectionProperty,
        \ReflectionClass    $reflectionClass,
        mixed               &$value
    ): void
    {
        $arrayType = $this->findArrayType($reflectionClass, $reflectionProperty);

        $this->castArrayByType(
            $arrayToObjectCaster,
            $value,
            $arrayType,
            $reflectionClass,
            $reflectionProperty
        );
    }

    private function findArrayType(\ReflectionClass $reflectionClass, \ReflectionProperty $reflectionProperty): string
    {
        $doccomment = $reflectionProperty->getDocComment();

        if ($doccomment === false) {
            throw new Exceptions\ArraysMustSpecifyContentType($reflectionProperty);
        }

        // Match TypeName[], TypeName[][], etc. — capture everything up to the final []
        if (preg_match('/@var\s+([\w\\\\]+(?:\[])*)\[]/', $doccomment, $match)) {
            return $match[1];
        }

        if (preg_match('/@var\s+array<(?:\w+, )?(\w+)>/', $doccomment, $match)
                && $type = $this->templateTypeFinder->find($match[1], $reflectionClass)) {
            return $type;
        }

        throw new Exceptions\ArraysMustSpecifyContentType($reflectionProperty);
    }

    private function castArrayByType(
        ArrayToObjectCaster $arrayToObjectCaster,
        mixed               &$value,
        string              $arrayType,
        \ReflectionClass    $reflectionClass,
        \ReflectionProperty $reflectionProperty
    ): void
    {
        if (str_ends_with($arrayType, '[]')) {
            // Nested array type (e.g., SomeClass[]): strip one level of [] and recurse into each child array
            $innerType = substr($arrayType, 0, -2);

            foreach ($value as &$child) {
                if (!is_array($child)) {
                    throw new Exceptions\CantCastValueToType(
                        $reflectionClass->name,
                        $reflectionProperty->name,
                        $child,
                        $arrayType
                    );
                }

                $this->castArrayByType(
                    $arrayToObjectCaster,
                    $child,
                    $innerType,
                    $reflectionClass,
                    $reflectionProperty
                );
            }

            return;
        }

        // Leaf type: resolve FQCN if needed, then cast each element
        if (!in_array($arrayType, PhpKeywords::INTERNAL_TYPES)) {
            $arrayType = $this->referenceToFqcn($arrayType, $reflectionClass);
        }

        foreach ($value as &$child) {
            if (!$this->checkValueType($arrayToObjectCaster, $child, $arrayType)) {
                throw new Exceptions\CantCastValueToType(
                    $reflectionClass->name,
                    $reflectionProperty->name,
                    $value,
                    $arrayType
                );
            }
        }
    }

    private function referenceToFqcn(string $childClass, \ReflectionClass $reflectionClass): string
    {
        if (str_starts_with($childClass, '\\')) {
            return $childClass;
        }

        $analysis = $this->classAnalyser->analyseClass($reflectionClass);
        $fqcn = $this->referenceResolver->resolveImport($analysis, $childClass);

        if ($fqcn === null) {
            $fqcn = ($analysis->namespace ? $analysis->namespace . '\\' : '') . $childClass;
        }

        return $fqcn;
    }

    public function checkValueType(ArrayToObjectCaster $arrayToObjectCaster, mixed &$value, string $typeName): bool
    {
        if ($typeName === 'mixed') {
            return true;
        }

        $currentType = get_debug_type($value);

        if ($currentType === $typeName) {
            // The value already has the right type
            return true;
        }

        if ($currentType === 'int' && $typeName === 'float') {
            $value = (float) $value;

            return true;
        }

        if (enum_exists($typeName)) {
            $value = $this->getEnumValue($value, new \ReflectionEnum($typeName));

            return true;
        }

        if (is_array($value) && class_exists($typeName)) {
            $value = $arrayToObjectCaster->cast($value, $typeName);

            return true;
        }

        if (is_string($value) && $this->serializeToClassNameManager->shouldSerializeToClassName($typeName)) {
            $constructor = new \ReflectionClass($value)->getConstructor();

            if ($constructor && $constructor->getNumberOfParameters() >= 1) {
                throw new Exceptions\ClassThatCastsToNameShouldntHaveConstructorArguments($value);
            }

            $value = new $value();

            return true;
        }

        return false;
    }

    private function getEnumValue(string|int $value, \ReflectionEnum $enum): \UnitEnum
    {
        /** @var class-string<\BackedEnum> $enumClass */
        $enumClass = $enum->getName();

        return $enum->isBacked() ? $enumClass::from($value) : $enum->getCase($value)->getValue();
    }
}
