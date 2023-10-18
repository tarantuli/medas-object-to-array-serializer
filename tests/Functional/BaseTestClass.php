<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\Functional;

use Medas\ObjectToArraySerializer\ObjectToArraySerializer;
use PHPUnit\Framework\TestCase;

abstract class BaseTestClass extends TestCase
{
    public function executeTest(object $object): void
    {
        $unserialized = $this->serializeThenUnserialize($object);

        self::assertInstanceOf($object::class, $unserialized);
        self::assertEqualsCanonicalizing($object, $unserialized);
    }

    public function executeTestShouldBeDifferent(object $object): void
    {
        $unserialized = $this->serializeThenUnserialize($object);

        self::assertInstanceOf($object::class, $unserialized);
        self::assertNotEqualsCanonicalizing($object, $unserialized);
    }

    public function serializeThenUnserialize(object $object): string|object
    {
        $serializer = service(ObjectToArraySerializer::class);
        $serialized = $serializer->serialize($object);
        self::assertIsArray($serialized);

        return $serializer->unserialize($serialized, class: $object::class);
    }
}
