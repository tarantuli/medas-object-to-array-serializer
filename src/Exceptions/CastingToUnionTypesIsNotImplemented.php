<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class CastingToUnionTypesIsNotImplemented extends BaseException
{
    public function __construct(\ReflectionProperty $property)
    {
        parent::__construct($property->getDeclaringClass()->getName(), $property->getName());
    }

    public function pattern(): string
    {
        return 'casting to union types is not implemented yet, cannot cast %s::%s';
    }
}
