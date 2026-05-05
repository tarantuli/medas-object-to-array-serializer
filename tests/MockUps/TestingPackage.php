<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

use Medas\Core\{AsSingleton, BasePackage};

class TestingPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return [];
    }

    public function isTestPackage(): bool
    {
        return true;
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }
}
