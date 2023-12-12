<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\PropertyHandlers;

use Medas\Core\{
    Attributes\Service,
    Interfaces\PropertyHandler as PropertyHandlerInterface,
    Interfaces\Type
};

#[Service]
readonly class PropertyHandler implements PropertyHandlerInterface
{
    public function type(): Type
    {
        // Not needed
    }

    /** @param int[] $value */
    public function serialize(mixed $value): string
    {
        return implode(',', $value);
    }

    /** @param string $value */
    public function unserialize(mixed $value): array
    {
        return explode(',', $value);
    }
}
