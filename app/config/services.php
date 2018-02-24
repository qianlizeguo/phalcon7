<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Flash\Direct as Flash;

function is_online()
{
    return APP_ENV === 'master';
}

function is_local()
{
    return APP_ENV === 'local' || APP_ENV === 'local_wu';
}

// XSS 过滤
function safe_replace($string)
{
    $string = str_replace('%20', '', $string);
    $string = str_replace('%27', '', $string);
    $string = str_replace('%2527', '', $string);

    /*$string = str_replace('"', '&quot;', $string);
    $string = str_replace("'", '', $string);
    $string = str_replace('"', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace('<', '&lt;', $string);
    $string = str_replace('>', '&gt;', $string);
    $string = str_replace('\\', '', $string);*/

    return $string;
}

function safe_filter($str)
{
    $filter = null;
    if (is_array($str)) {
        foreach ($str as $key => $val) {
            $filter[safe_replace($key)] = safe_filter($val);
        }
    } else {
        $filter = safe_replace($str);
    }

    return $filter;
}

if ($_GET) {
    $_GET = safe_filter($_GET);
}
if ($_POST) {
    $_POST = safe_filter($_POST);
}
if ($_REQUEST) {
    $_REQUEST = safe_filter($_REQUEST);
}
if ($_COOKIE) {
    $_COOKIE = safe_filter($_COOKIE);
}

// 加载项目选项
$project = 'public';
$controllerDir = APP_PATH . '/controllers/public/';

//if (strpos($_SERVER['REQUEST_URI'], 'user') === 1) {
//    $project = 'user';
//    $controllerDir = APP_PATH . '/controllers/user/';
//}

/**
 * Shared configuration service
 */
//$di->setShared('config', function () {
//    return include APP_PATH . "/config/config.php";
//});

$config = include APP_PATH . '/config/config.global.php';
if (file_exists(APP_PATH . '/config/config.' . APP_ENV . '.php')) {
    $overrideConfig = include APP_PATH . '/config/config.' . APP_ENV . '.php';
    $config->merge($overrideConfig);
}
$di->set('config', $config, true);

define('APP_LOG', $di->getConfig()->logPath.APP_NAME.'/');

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
});

/**
 * Setting up the view component
 */
$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines([
        '.phtml' => PhpEngine::class

    ]);

    return $view;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
foreach ((array) $di->get('config')->databases as $key => $database) {
    $di->set($key, function () use ($database) {
        $class = 'Phalcon\Db\Adapter\Pdo\\' . $database->adapter;
        $data = $database->toArray();
        unset($data['adapter']);
        return new $class($data);
    });
}

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function () {
    return new Flash([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionAdapter();
    $session->start();

    return $session;
});


class Request extends Phalcon\Http\Request
{
    public function getClientAddress($trustForwardedHeader = null)
    {
        $ip = parent::getClientAddress();
        if (isset($_SERVER['HTTP_USERIP'])) {
            $ip = $_SERVER['HTTP_USERIP'];
        } elseif (isset($_SERVER['HTTP_ALI_CDN_REAL_IP'])) {
            $ip = $_SERVER['HTTP_ALI_CDN_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && $_SERVER['HTTP_X_FORWARDED_FOR']
        ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ($ip) {
            $ip = safe_filter($ip);
        }

        $ip = explode(',', $ip);
        $ip = trim($ip[0]);

        return $ip;
    }
}

$di->set('request', function () {
    return new Request;
});

//Registering the router component
$di->set( 'router', function () use ($project) {
    return require APP_PATH . '/config/route.' . $project . '.php';
});
