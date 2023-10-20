<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\WithConstructors;

class HasRequiredConstructor
{
    public int $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }
}
