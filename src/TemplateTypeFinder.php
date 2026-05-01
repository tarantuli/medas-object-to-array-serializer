<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\Attributes\Service;

#[Service]
readonly class TemplateTypeFinder
{
    public function find(string $name, \ReflectionClass $class): string|null
    {
        [$parentClassName, $assignments] = $this->findAssignments($class);

        if ($assignments === null) {
            return null;
        }

        $names = $this->findNamesInParent($class, $parentClassName);

        if ($names === null || false === $index = array_search($name, $names)) {
            return null;
        }

        return $assignments[$index] ?? null;
    }

    /**
     * Parses the `@extends ParentClass<A, B>` annotation on `$class`.
     *
     * @return array{0: string|null, 1: array|null} [$parentClassName, $assignments]
     * @noinspection PhpUndefinedClassInspection
     */
    private function findAssignments(\ReflectionClass $class): array
    {
        $doccomment = $class->getDocComment();

        if ($doccomment === false) {
            return [null, null];
        }

        if (preg_match('/@extends\s+([\w\\\\]+)<((?:\w+, ?)*\w+)>/', $doccomment, $match)) {
            return [$match[1], preg_split('/, ?/', $match[2])];
        }

        return [null, null];
    }

    /**
     * Walks up the class hierarchy to find the specific parent class named in `@extends`,
     * then reads its `@template` parameter names. This ensures the positional mapping
     * between `@extends Parent<A, B>` and `@template A, B` is always read from the
     * correct class, regardless of how many ancestors exist.
     *
     * @return array|null The list of template parameter names defined on the parent, or null if not found.
     * @noinspection PhpUndefinedClassInspection
     */
    private function findNamesInParent(\ReflectionClass $class, string $parentClassName): array|null
    {
        $parent = $class->getParentClass();

        while ($parent !== false) {
            if ($parent->getShortName() === $parentClassName || $parent->getName() === $parentClassName) {
                $doccomment = $parent->getDocComment();

                if ($doccomment === false) {
                    return null;
                }

                if (preg_match('/@template ((?:\w+, ?)*\w+)/', $doccomment, $match)) {
                    return preg_split('/, ?/', $match[1]);
                }

                // Found the named parent, but it has no @template — no point going further
                return null;
            }

            $parent = $parent->getParentClass();
        }

        return null;
    }
}
