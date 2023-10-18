<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

use Medas\ObjectToArraySerializer\DontSerializeMe;

class PropertyToSkip
{
    #[DontSerializeMe]
    public int $dontSerializeMe = 0;

    public int $doSerializeMe = 10;
}
