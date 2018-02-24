<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/24
 * Time: 17:22
 */

// 路由
$routers = [
    '/public/test' => array(
        'controller' => 'Index',
        'action' => 'index',
        'method' => 'get'
    ),
];

$router = new Phalcon\Mvc\Router(false);
foreach ($routers as $key => $val) {
    $router->add(
        $key,
        $val
    );
}

return $router;
