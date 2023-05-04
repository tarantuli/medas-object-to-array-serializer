<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializerTest\MockUps;

class EmbeddedClass
{
    public BasicClass $basicClass;
    public PrivateProperties $privateProperties;
    public ElevatedProperties $elevatedProperties;
}
