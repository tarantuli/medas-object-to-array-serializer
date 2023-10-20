<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\WithConstructors;

readonly class HolderOfIllegalClasses
{
    public CastsToClassNameWithConstructorArgument $class;

    public function __construct()
    {
        $this->class = new CastsToClassNameWithConstructorArgument(10);
    }
}
