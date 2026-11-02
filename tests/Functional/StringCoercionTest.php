<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\Functional;

use Medas\ObjectToArraySerializer\{
    ArrayToObjectCaster,
    ArrayToObjectCaster\Settings,
    Exceptions\CantCastValueToType
};
use Medas\ObjectToArraySerializerTest\MockUps\{Enums\IntBackedEnum, StringCoercion\QueryValues};
use PHPUnit\Framework\TestCase;

class StringCoercionTest extends TestCase
{
    private const array QUERY = [
        'count' => '3',
        'ratio' => '2.5',
        'flag' => 'true',
        'otherFlag' => '0',
        'text' => '42',
        'enum' => '2',
        'ids' => ['1', '-7'],
        'child' => ['limit' => '50'],
    ];

    public function testStringsAreCoercedWhenAsked(): void
    {
        /** @var QueryValues $object */
        $object = service(ArrayToObjectCaster::class)->cast(
            self::QUERY,
            QueryValues::class,
            new Settings(skipUnknownValues: true, coerceStrings: true)
        );

        self::assertSame(3, $object->count);
        self::assertSame(2.5, $object->ratio);
        self::assertTrue($object->flag);
        self::assertFalse($object->otherFlag);
        self::assertNull($object->nullable);

        // A string property keeps a numeric string as it is
        self::assertSame('42', $object->text);
        self::assertSame(IntBackedEnum::B, $object->enum);
        self::assertSame([1, -7], $object->ids);

        // Nested objects get the same settings
        self::assertSame(50, $object->child->limit);
    }

    public function testStringsAreNotCoercedByDefault(): void
    {
        $this->expectException(CantCastValueToType::class);

        service(ArrayToObjectCaster::class)->cast(['count' => '3'], QueryValues::class);
    }

    public function testStringsThatDontSpellTheTypeStillFail(): void
    {
        $this->expectException(CantCastValueToType::class);

        service(ArrayToObjectCaster::class)->cast(
            ['count' => '3 apples'],
            QueryValues::class,
            new Settings(skipUnknownValues: true, coerceStrings: true)
        );
    }
}
