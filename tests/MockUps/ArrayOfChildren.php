<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

use Medas\ObjectToArraySerializerTest\MockUps\Directory\ImportedChild;

class ArrayOfChildren
{
    /** @var ElevatedProperties[] */
    public array $typedChildren;

    /** @var Directory\RelativeChild[] */
    public array $relativeChildren;

    /** @var ImportedChild[] */
    public array $importChildren;

    /** @var \Medas\ObjectToArraySerializerTest\MockUps\Directory\AbsoluteChild[]
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public array $absoluteChildren;
}
