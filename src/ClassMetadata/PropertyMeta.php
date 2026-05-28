<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\ClassMetadata;

use Medas\Core\Interfaces\PropertyHandler;

readonly class PropertyMeta
{
    public function __construct(
        public string               $name,
        public \ReflectionProperty  $reflection,
        public PropertyHandler|null $handler,
    )
    {
    }
}
