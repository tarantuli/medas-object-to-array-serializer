<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\Attributes\Service;

#[Service]
readonly class TemplateTypeFinder
{
    public function find(string $name, \ReflectionClass $class): string|null
    {
        $assignments = $this->findAssignments($class);
        $names = $this->findNames($class);

        if (false === $index = array_search($name, $names)) {
            return null;
        }

        return $assignments[$index] ?? null;
    }

    private function findAssignments(\ReflectionClass $class): array|null
    {
        $doccomment = $class->getDocComment();

        if ($doccomment === false) {
            return null;
        }

        if (preg_match('/@extends\s+([\w\\\]+)<((?:\w+, ?)*\w+)>/', $doccomment, $match)) {
            // TODO $match[1] contains the extended class name which should be used to read the actual template indices
            return preg_split('/, ?/', $match[2]);
        }

        return null;
    }

    private function findNames(\ReflectionClass $class): array|null
    {
        do {
            $doccomment = $class->getDocComment();

            if ($doccomment === false) {
                continue;
            }

            if (preg_match('/@template ((?:\w+, ?)*\w+)/', $doccomment, $match)) {
                return preg_split('/, ?/', $match[1]);
            }
        } while ($class = $class->getParentClass());

        return null;
    }
}
