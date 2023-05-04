<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

class ElevatedProperties
{
    public function __construct(
        public int              $id,
        protected string        $protectedString,
        private readonly string $privateString,
    )
    {
    }

    public function privateString(): string
    {
        return $this->privateString;
    }
}
