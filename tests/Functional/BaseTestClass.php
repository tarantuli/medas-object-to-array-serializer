<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\Functional;

use Medas\ObjectToArraySerializer\ObjectToArraySerializer;
use PHPUnit\Framework\TestCase;

abstract class BaseTestClass extends TestCase
{
    public function executeTest(object $object): void
    {
        $serializer = service(ObjectToArraySerializer::class);
        $serialized = $serializer->serialize($object);
        self::assertIsArray($serialized);

        $unserialized = $serializer->unserialize($serialized, class: $object::class);

        self::assertInstanceOf($object::class, $unserialized);
        self::assertEqualsCanonicalizing($object, $unserialized);
    }
}
