<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{Attributes\Service, Interfaces\ObjectToArrayHandler};
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
        ArrayToObjectCaster               $arrayToObjectCaster,
        \ReflectionProperty               $reflectionProperty,
        \ReflectionClass                  $reflectionClass,
        mixed                             &$value,
        ArrayToObjectCaster\Settings|null $settings = null,
    ): void
    {
        $arrayType = $this->findArrayType($reflectionClass, $reflectionProperty);

        $this->castArrayByType(
            $arrayToObjectCaster,
            $value,
            $arrayType,
            $reflectionClass,
            $reflectionProperty,
            $settings
        );
    }

    private function findArrayType(\ReflectionClass $reflectionClass, \ReflectionProperty $reflectionProperty): string
    {
        $doccomment = $reflectionProperty->getDocComment();

        if ($doccomment === false) {
            return 'mixed';
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
        ArrayToObjectCaster               $arrayToObjectCaster,
        mixed                             &$value,
        string                            $arrayType,
        \ReflectionClass                  $reflectionClass,
        \ReflectionProperty               $reflectionProperty,
        ArrayToObjectCaster\Settings|null $settings = null,
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
                    $reflectionProperty,
                    $settings
                );
            }

            return;
        }

        // Leaf type: resolve FQCN if needed, then cast each element
        if (!in_array($arrayType, PhpKeywords::INTERNAL_TYPES)) {
            $arrayType = $this->referenceToFqcn($arrayType, $reflectionClass);
        }

        foreach ($value as &$child) {
            if (!$this->checkValueType($arrayToObjectCaster, $child, $arrayType, $settings)) {
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

    public function checkValueType(
        ArrayToObjectCaster               $arrayToObjectCaster,
        mixed                             &$value,
        string                            $typeName,
        ArrayToObjectCaster\Settings|null $settings = null,
    ): bool
    {
        if (is_array($value)) {
            $firstKey = array_key_first($value);

            if (is_string($firstKey) && str_starts_with($firstKey, ObjectToArrayCaster::HANDLER_KEY_PREFIX)) {
                // This uses a custom handler, which we extract from the key, then use
                $handlerClass = substr($firstKey, strlen(ObjectToArrayCaster::HANDLER_KEY_PREFIX));

                /** @var ObjectToArrayHandler $handler */
                $handler = \service($handlerClass);
                $value = $handler->toObject($value[$firstKey]);

                return true;
            }
        }

        if ($typeName === 'mixed') {
            return true;
        }

        $currentType = get_debug_type($value);

        if ($currentType === $typeName) {
            // The value already has the right type
            return true;
        }

        if ($settings?->coerceStrings && is_string($value) && $this->coerceString($value, $typeName)) {
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
            $value = $arrayToObjectCaster->cast($value, $typeName, $settings);

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

    /**
     * Turns a string into the int, float or bool it spells, when it does: "3", "-1", "2.5", "true",
     * "false", "1", "0", "yes", "no", "on", "off"; and into the case of an int-backed enum. Other
     * types and strings are left alone, so they fail the usual way.
     */
    private function coerceString(mixed &$value, string $typeName): bool
    {
        if (enum_exists($typeName)) {
            $enum = new \ReflectionEnum($typeName);

            if ((string) $enum->getBackingType() !== 'int' || !preg_match('#^-?\d+$#', $value)) {
                return false;
            }

            $value = $this->getEnumValue((int) $value, $enum);

            return true;
        }

        $coerced = match ($typeName) {
            'int' => preg_match('#^-?\d+$#', $value) ? (int) $value : null,
            'float' => is_numeric($value) ? (float) $value : null,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            default => null,
        };

        if ($coerced === null) {
            return false;
        }

        $value = $coerced;

        return true;
    }

    private function getEnumValue(string|int $value, \ReflectionEnum $enum): \UnitEnum
    {
        /** @var class-string<\BackedEnum> $enumClass */
        $enumClass = $enum->getName();

        return $enum->isBacked() ? $enumClass::from($value) : $enum->getCase($value)->getValue();
    }
}
