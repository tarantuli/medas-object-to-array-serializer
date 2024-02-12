<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\Exceptions;

use Medas\Core\Exceptions\BaseException;

class PropertyDoesNotExist extends BaseException
{
    public function __construct(object $object, string $propertyName)
    {
        parent::__construct($object::class, $propertyName);
    }

    public function pattern(): string
    {
        return 'Class %s does not have a property named %s';
    }
}
