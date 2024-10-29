<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\Functional;

use Medas\ObjectToArraySerializer\Exceptions\ClassThatCastsToNameShouldntHaveConstructorArguments;
use Medas\ObjectToArraySerializerTest\MockUps\{ArrayOfChildren,
    ArrayOfEnums,
    ArrayOfInternalTypes,
    BasicClass,
    ClosureClass,
    Directory\AbsoluteChild,
    Directory\ImportedChild,
    Directory\RelativeChild,
    ElevatedProperties,
    EmbeddedClass,
    EmptyValues,
    EnumProperties,
    Enums\IntBackedEnum,
    Enums\StringBackedEnum,
    Enums\UnbackedEnum,
    MixedProperties,
    ObjectToClassName\ExtendedClass,
    ObjectToClassName\HolderClass,
    PrivateProperties,
    PropertyHandlers\WithPropertyHandler,
    PropertyToSkip,
    TemplateTypes\TemplateExtendingClass,
    WithConstructors\HolderOfIllegalClasses,
    WithConstructors\HolderOfLegalClasses};

class BasicTest extends BaseTestClass
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
        $object = new EnumProperties(UnbackedEnum::B, IntBackedEnum::B, StringBackedEnum::B);

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

    public function testClosures(): void
    {
        $object = new ClosureClass();
        $object->property1 = 'cheese';
        $object->closure = mt_rand(...);

        $this->executeTest($object);
    }

    public function testTemplateTypes(): void
    {
        $object = new TemplateExtendingClass();
        $object->elements = [new BasicClass()];
        $object->intIndexed = [new BasicClass(), new BasicClass()];

        $this->executeTest($object);
    }

    public function testObjectTOClassName(): void
    {
        $object = new HolderClass(new ExtendedClass());

        $this->executeTest($object);
    }

    public function testArrayOfInternalTypes(): void
    {
        $object = new ArrayOfInternalTypes(['John', 'Mary']);

        $this->executeTest($object);
    }

    public function testDontSerializeMe(): void
    {
        $object = new PropertyToSkip();
        $object->dontSerializeMe = 20;
        $object->doSerializeMe = 30;

        // The value that mustn't be serialized must be its default value again, so the result of serializing
        // then unserializing should be different from the original object
        $this->executeTestShouldBeDifferent($object);
    }

    public function testConstructors(): void
    {
        $object = new HolderOfLegalClasses();

        $this->executeTest($object);

        $object = new HolderOfIllegalClasses();

        $this->expectException(ClassThatCastsToNameShouldntHaveConstructorArguments::class);
        $this->executeTest($object);
    }

    public function testPropertyHandler(): void
    {
        $object = new WithPropertyHandler();
        $object->properties = [1, 2, 3, 4];

        $this->executeTest($object);
    }

    public function testEmptyValues(): void
    {
        $object = new EmptyValues();

        $this->executeTest($object);
    }
}
