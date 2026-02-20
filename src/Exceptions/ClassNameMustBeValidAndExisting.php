<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ClassNameMustBeValidAndExisting extends BaseException
{
    public function __construct(string|null $className)
    {
        parent::__construct($className);
    }

    public function pattern(): string
    {
        return 'class name must be given and class must exist, %s given';
    }
}
