<?php

declare(strict_types=1);

use Medas\ObjectToArraySerializer\ObjectToArraySerializerPackage;
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig();

    $config->addPackages([
        ObjectToArraySerializerPackage::instance(),
    ]);

    return $config;
});
