<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ArraysMustSpecifyContentType extends BaseException
{
    public function __construct(\ReflectionProperty $property)
    {
        parent::__construct($property->getName(), $property->getDeclaringClass()->name);
    }

    public function pattern(): string
    {
        return 'array property %s of class %s must specify the type of its content in its doccomment as "@var TypeName[]" or "@var array<TypeName>"';
    }
}
