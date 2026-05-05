<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

use Medas\ObjectToArraySerializer\DontSerializeEmptyValues;

#[DontSerializeEmptyValues]
class EmptyValues
{
    // Empty values
    public string $emptyString = '';
    public null $null = null;

    /** @var string[] */
    public array $emptyArray = [];

    public bool $false = false;
    public int $zero = 0;
    public string $zeroString = "0";

    // Non-empty values
    public bool $true = true;

    /** @var string[] */
    public array $arrayWithEmptyString = [''];
}
