<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\WithConstructors;

use Medas\ObjectToArraySerializer\SerializeToClassName\CastToClassName;

#[CastToClassName]
class CastsToClassNameWithConstructorArgument
{
    public int $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }
}
