<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

readonly class EnumProperties
{
    public function __construct(
        public Enums\UnbackedEnum     $unbackedEnum,
        public Enums\IntBackedEnum    $intBackedEnum,
        public Enums\StringBackedEnum $stringBackedEnum,
    )
    {
    }
}
