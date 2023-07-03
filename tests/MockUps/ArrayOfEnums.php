<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

class ArrayOfEnums
{
    /** @var Enums\IntBackedEnum[] */
    public array $enums;

    public function __construct()
    {
        $this->enums = [
            Enums\IntBackedEnum::A,
            Enums\IntBackedEnum::B,
        ];
    }
}
