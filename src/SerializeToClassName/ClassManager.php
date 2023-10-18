<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\SerializeToClassName;

use Medas\Core\Attributes\Service;
use Medas\Core\Interfaces\CacheManager;

#[Service]
readonly class ClassManager
{
    public function __construct(
        private CacheManager $cacheManager,
    )
    {
    }

    public function shouldSerializeToClassName(string $className): bool
    {
        return $this->cacheManager->get()->get(
            [__CLASS__, $className],
            fn() => $this->determine($className)
        );
    }

    private function determine(string $className): bool
    {
        $class = new \ReflectionClass($className);

        do {
            if ($class->getAttributes(CastToClassName::class)) {
                return true;
            }
        } while ($class = $class->getParentClass());

        return false;
    }
}
