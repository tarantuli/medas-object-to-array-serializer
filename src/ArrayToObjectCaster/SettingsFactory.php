<?php

declare(strict_types=1);

namespace Medas\ObjectToArraySerializer\ArrayToObjectCaster;

use Medas\Core\Attributes\Service;

#[Service]
readonly class SettingsFactory
{
    public function create(): Settings
    {
        return new Settings(skipUnknownValues: true);
    }
}
