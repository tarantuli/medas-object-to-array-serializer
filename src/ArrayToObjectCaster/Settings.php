<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\ArrayToObjectCaster;

class Settings
{
    public function __construct(
        public bool $skipUnknownValues,
    )
    {
    }
}
