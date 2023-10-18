<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

class ArrayOfInternalTypes
{
    public function __construct(
        /** @var string[] */
        public array $names,
    )
    {
    }
}
