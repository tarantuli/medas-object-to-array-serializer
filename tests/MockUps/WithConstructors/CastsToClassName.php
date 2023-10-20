<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\WithConstructors;

use Medas\ObjectToArraySerializer\SerializeToClassName\CastToClassName;
use Medas\ObjectToArraySerializerTest\MockUps\BasicClass;

#[CastToClassName]
class CastsToClassName
{
    public BasicClass $basicClass;

    public function __construct()
    {
        $this->basicClass = new BasicClass();
    }
}
