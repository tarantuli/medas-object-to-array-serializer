<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\ArrayToObjectCaster;

class Settings
{
    public function __construct(
        public bool $skipUnknownValues,

        /**
         * Accept strings for int, float and bool properties ("3", "2.5", "true"), as found in query
         * strings and form data, where every value is a string. Off by default: a string where a
         * number is expected is usually a mistake.
         */
        public bool $coerceStrings = false,
    )
    {
    }
}
