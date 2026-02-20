<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer;

/**
 * When applied to a class, properties whose value is considered empty will be omitted from the serialized array.
 *
 * A value is considered empty if PHP's {@see empty()} returns true for it, with two exceptions:
 * the empty string ('') and the string zero ('0') are never omitted.
 *
 * In practice this means the following values ARE omitted: null, [], false, 0, 0.0
 * And the following values are NOT omitted: '', '0', true, any non-empty array, any non-zero number
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DontSerializeEmptyValues
{
}
