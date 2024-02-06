<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

#[\Attribute(\Attribute::TARGET_CLASS)]
class DontSerializeEmptyValues
{
}
