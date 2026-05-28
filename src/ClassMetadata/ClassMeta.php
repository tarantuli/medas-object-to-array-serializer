<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\ClassMetadata;

use Medas\Core\Interfaces\ObjectToArrayHandler as ObjectToArrayHandlerInterface;

readonly class ClassMeta
{
    /**
     * @param PropertyMeta[] $properties
     */
    public function __construct(
        public string|null                        $objectHandlerClass,
        public ObjectToArrayHandlerInterface|null $objectHandler,
        public bool                               $dontSerializeEmptyValues,
        public array                              $properties,
    )
    {
    }
}
