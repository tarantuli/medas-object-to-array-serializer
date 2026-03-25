<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

use Medas\Core\{AsSingleton, BasePackage};
use Medas\PhpClassAnalysis\PhpClassAnalysisPackage;

class ObjectToArraySerializerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return [
            PhpClassAnalysisPackage::instance(),
        ];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }
}
