<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\SerializeToClassName;

/**
 * Classes marked with this attribute should be cast to their class name when serializing.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class CastToClassName
{
}
