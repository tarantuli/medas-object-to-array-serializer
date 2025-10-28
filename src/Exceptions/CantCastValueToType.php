<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class CantCastValueToType extends BaseException
{
    public function __construct(string $className, string $propertyName, mixed $value, string $typeName)
    {
        parent::__construct($className, $propertyName, $value, get_debug_type($value), $typeName);
    }

    public function pattern(): string
    {
        return '[%s->%s] failed to cast value %s of type %s to type %s';
    }
}
