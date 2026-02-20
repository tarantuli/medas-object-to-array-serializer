<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ArraysMustSpecifyContentType extends BaseException
{
    public function __construct(\ReflectionProperty $property)
    {
        parent::__construct($property->getName());
    }

    public function pattern(): string
    {
        return 'array property %s must specify the type of its content in its doccomment as "@var TypeName[]" or "@var array<TypeName>"';
    }
}
