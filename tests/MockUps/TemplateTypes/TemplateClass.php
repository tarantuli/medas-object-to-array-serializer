<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\TemplateTypes;

/** @template T */
abstract class TemplateClass
{
    /** @var array<T> */
    public array $elements;

    /** @var array<int, T> */
    public array $intIndexed;
}
