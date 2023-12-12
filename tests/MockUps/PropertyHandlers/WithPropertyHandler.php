<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\PropertyHandlers;

use Medas\EntityManager\Attributes\Handler;

class WithPropertyHandler
{
    #[Handler(PropertyHandler::class)]
    public array $properties;
}
