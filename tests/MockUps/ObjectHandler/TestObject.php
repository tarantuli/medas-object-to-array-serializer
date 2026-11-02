<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\ObjectHandler;

use Medas\Core\Interfaces\{HasId, Uuid};

readonly class TestObject implements HasId
{
    public function __construct(
        private Uuid $uuid,
    )
    {
    }

    public function id(): Uuid
    {
        return $this->uuid;
    }
}
