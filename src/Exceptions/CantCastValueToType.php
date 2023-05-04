<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class CantCastValueToType extends BaseException
{
    public function __construct(mixed $value, string $typeName)
    {
        parent::__construct($value, $typeName);
    }

    public function pattern(): string
    {
        return 'failed to cast value %s to type %s';
    }
}
