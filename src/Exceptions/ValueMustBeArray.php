<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ValueMustBeArray extends BaseException
{
    public function __construct(mixed $value)
    {
        parent::__construct(get_debug_type($value));
    }

    public function pattern(): string
    {
        return 'value must be an array, %s given';
    }
}
