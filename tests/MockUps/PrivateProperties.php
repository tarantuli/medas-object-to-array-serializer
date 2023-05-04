<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

class PrivateProperties
{
    public int $id;
    protected string $protectedValue;
    protected string $protectedValueWithDefault = 'test';
    private string $privateValue;
    private string $privateValueWithDefault = 'hi';
}
