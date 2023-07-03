<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\Functional;

use Medas\ObjectToArraySerializerTest\MockUps\{ArrayOfChildren,
    ArrayOfEnums,
    BasicClass,
    Directory\AbsoluteChild,
    Directory\ImportedChild,
    Directory\RelativeChild,
    ElevatedProperties,
    EmbeddedClass,
    EnumProperties,
    Enums\IntBackedEnum,
    Enums\StringBackedEnum,
    Enums\UnbackedEnum,
    MixedProperties,
    PrivateProperties
};

class BasicTests extends BaseTestClass
{
    public function testBasicClass(): void
    {
        $object = new BasicClass();
        $object->id = 1;
        $object->name = 'Test';

        $this->executeTest($object);
    }

    public function testPrivateProperties(): void
    {
        $object = new PrivateProperties();
        $object->id = 1;

        $this->executeTest($object);
    }

    public function testElevatedProperties(): void
    {
        $object = new ElevatedProperties(1, 'isProtected', 'isPrivate');

        $this->executeTest($object);
    }

    public function testEmbeddedClass(): void
    {
        $object = new EmbeddedClass();
        $object->basicClass = new BasicClass();
        $object->basicClass->name = 'child class';
        $object->privateProperties = new PrivateProperties();
        $object->elevatedProperties = new ElevatedProperties(10, '11', '12');

        $this->executeTest($object);
    }

    public function testArrayOfChildren(): void
    {
        $object = new ArrayOfChildren();

        $object->typedChildren = [
            new ElevatedProperties(100, 'protectedA', 'privateA'),
            new ElevatedProperties(50, 'protectedB', 'privateC'),
        ];

        $object->relativeChildren = [new RelativeChild()];
        $object->importChildren = [new ImportedChild()];
        $object->absoluteChildren = [new AbsoluteChild()];

        $this->executeTest($object);
    }

    public function testEnums(): void
    {
        $object = new EnumProperties(
            UnbackedEnum::B,
            IntBackedEnum::B,
            StringBackedEnum::B
        );

        $this->executeTest($object);
    }

    public function testArrayOfEnums(): void
    {
        $object = new ArrayOfEnums();

        $this->executeTest($object);
    }

    public function testMixed(): void
    {
        $object = new MixedProperties();
        $object->property1 = ['cheese'];

        $this->executeTest($object);
    }
}
