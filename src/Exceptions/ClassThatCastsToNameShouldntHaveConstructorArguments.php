<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ClassThatCastsToNameShouldntHaveConstructorArguments extends BaseException
{
    public function __construct(string $className)
    {
        parent::__construct($className);
    }

    public function pattern(): string
    {
        return 'class %s that is cast to its name should not have arguments in the constructor';
    }
}
