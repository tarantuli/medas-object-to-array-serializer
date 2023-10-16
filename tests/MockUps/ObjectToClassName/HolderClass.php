<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\ObjectToClassName;

readonly class HolderClass
{
    public function __construct(
        public BaseClass $variable,
    )
    {
    }
}
