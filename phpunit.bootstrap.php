<?php

declare(strict_types=1);

use Medas\EntityManager\EntityManagerPackage;
use Medas\ObjectToArraySerializer\ObjectToArraySerializerPackage;
use Medas\ObjectToArraySerializerTest\MockUps\TestingPackage;
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig();

    $config->addPackages([
        ObjectToArraySerializerPackage::instance(),
        EntityManagerPackage::instance(),
        TestingPackage::instance(),
    ]);

    return $config;
});
