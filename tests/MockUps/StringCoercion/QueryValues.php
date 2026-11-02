<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps\StringCoercion;

use Medas\ObjectToArraySerializerTest\MockUps\Enums\IntBackedEnum;

class QueryValues
{
    public int $count;
    public float $ratio;
    public bool $flag;
    public bool $otherFlag;
    public int|null $nullable = null;
    public string $text;
    public IntBackedEnum $enum;

    /** @var int[] */
    public array $ids = [];

    public QueryChild $child;
}
