<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{
    Attributes\Entrypoint,
    Attributes\Handler,
    Attributes\Service,
    Interfaces\PropertyHandler
};
use Medas\PhpClassAnalysis\{ClassAnalyser, PhpKeywords};

#[Service, Entrypoint]
readonly class ArrayToObjectCaster
{
    public function __construct(
        private ArrayToObjectCaster\SettingsFactory $settingsFactory,
        private ClassAnalyser                       $classAnalyser,
        private SerializeToClassName\ClassManager   $serializeToClassNameManager,
        private TemplateTypeFinder                  $templateTypeFinder,
    )
    {
    }

    public function cast(array $values, string $className, ArrayToObjectCaster\Settings $settings = null): object
    {
        $settings ??= $this->settingsFactory->create();
        $reflectionClass = new \ReflectionClass($className);
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

                    if ($typeName === 'array' && is_iterable($value)) {
                        $this->castArrayMembers($reflectionProperty, $reflectionClass, $value);
                    }

                    $this->checkValueType($value, $typeName);
                }
            }

            if (!$reflectionProperty->isReadOnly() || !$reflectionProperty->isInitialized($object)) {
                $reflectionProperty->setValue($object, $value);
            }
        }

        return $object;
    }

    private function castArrayMembers(
        \ReflectionProperty $reflectionProperty,
        \ReflectionClass    $reflectionClass,
        mixed               &$value
    ): void
    {
        $arrayType = $this->findArrayType($reflectionClass, $reflectionProperty);

        if (!in_array($arrayType, PhpKeywords::INTERNAL_TYPES)) {
            $arrayType = $this->referenceToFqcn($arrayType, $reflectionClass);
        }

        foreach ($value as &$child) {
            $this->checkValueType($child, $arrayType);
        }
    }

    private function findArrayType(\ReflectionClass $reflectionClass, \ReflectionProperty $reflectionProperty): string
    {
        $doccomment = $reflectionProperty->getDocComment();

        if ($doccomment === false) {
            throw new Exceptions\ArraysMustSpecifyContentType($reflectionProperty);
        }

        if (preg_match('/@var\s+([\w\\\]+)\[]/', $doccomment, $match)) {
            return $match[1];
        }

        if (preg_match('/@var\s+array<(?:\w+, )?(\w+)>/', $doccomment, $match)
                && $type = $this->templateTypeFinder->find($match[1], $reflectionClass)) {
            return $type;
        }

        throw new Exceptions\ArraysMustSpecifyContentType($reflectionProperty);
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

    private function checkValueType(mixed &$value, string $typeName): void
    {
        if ($typeName === 'mixed') {
            return;
        }

        $currentType = get_debug_type($value);

        if ($currentType === $typeName) {
            // The value already has the right type
            return;
        }

        if ($currentType === 'int' && $typeName === 'float') {
            $value = (float) $value;

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

        if (is_string($value) && $this->serializeToClassNameManager->shouldSerializeToClassName($typeName)) {
            $constructor = (new \ReflectionClass($value))->getConstructor();

            if ($constructor && $constructor->getNumberOfParameters() >= 1) {
                throw new Exceptions\ClassThatCastsToNameShouldntHaveConstructorArguments($value);
            }

            $value = new $value();

            return;
        }

        throw new Exceptions\CantCastValueToType($value, $typeName);
    }

    private function getEnumValue(array $value, \ReflectionEnum $enum): \UnitEnum
    {
        return $enum->getCase($value['name'])->getValue();
    }
}
