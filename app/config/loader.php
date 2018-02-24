<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
// 根据命名空间前缀加载
$loader->registerNamespaces([
    'App\Library'     => APP_PATH.'/library/',
    'App\Model'     => APP_PATH.'/models/',
])->register();

$loader->registerDirs([
    $controllerDir,
])->register();

//$loader->registerDirs(
//    [
//        $config->application->controllersDir,
//        $config->application->modelsDir
//    ]
//)->register();
