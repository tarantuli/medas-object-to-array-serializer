<?php

declare(strict_types=1);

use Medas\ObjectInstantiator\ObjectInstantiator;
use Medas\ObjectToArraySerializer\ObjectToArraySerializerPackage;
use Medas\ObjectToArraySerializerTest\MockUps\TestingPackage;
use Medas\RamseyUuidBridge\RamseyUuidBridgePackage;
use Medas\ServiceManager\{ServiceConfigBuilder, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfigBuilder {
    $config = new ServiceConfigBuilder(ObjectInstantiator::class);

    $config->addPackages([
        ObjectToArraySerializerPackage::instance(),
        TestingPackage::instance(),
        RamseyUuidBridgePackage::instance(),
    ]);

    return $config;
});
