<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\WithConstructors;

readonly class HolderOfLegalClasses
{
    public HasRequiredConstructor $hasRequiredConstructor;
    public CastsToClassName $castsToClassName;

    public function __construct()
    {
        $this->hasRequiredConstructor = new HasRequiredConstructor(10);
        $this->castsToClassName = new CastsToClassName();
    }
}
