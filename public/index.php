<?php
define("DS", DIRECTORY_SEPARATOR);
define("ROOT", realpath(dirname(__DIR__)) . DS);
define("VENDORDIR", ROOT . "vendor" . DS);
define("STORAGEDIR", ROOT . "storage" . DS);
define("CACHEDIR", ROOT . "cache" . DS);
define("SRCDIR", ROOT . "src" . DS);
define("PARSESCRIPTSDIR", ROOT . "src" . DS . "scripts" . DS);
define("PUBLICDIR", ROOT . DS . "public" . DS);
define('DEBUGDIR', ROOT . "debug" . DS);
define("UPLOADDIR", ROOT . "public" . DS . 'roadinfo' . DS . 'uploads' . DS);
define("LAYERS_ICONS", ROOT . "public" . DS . 'roadinfo' . DS . 'resources' . DS . 'layers' . DS . 'icons' . DS);

if (!is_dir(SRCDIR)) die("<pre>directory </pre>" . SRCDIR . "<pre> not exists</pre>");
if (!is_dir(UPLOADDIR)) die("<pre>directory </pre>" . UPLOADDIR . "<pre> not exists</pre>");
if (!is_dir(LAYERS_ICONS)) die("<pre>directory </pre>" . LAYERS_ICONS . "<pre> not exists</pre>");

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

//error_reporting(0);
//ini_set('display_errors', 0);

if (!file_exists(VENDORDIR . "autoload.php")) {
    die("<pre>Run 'composer.phar install' in root dir</pre>");
}

if (!file_exists(ROOT . 'config' . DS . 'database.config.php')) {
    die("<pre>Rename 'config/database.config.php.install' to 'config/database.config.php' and configure your connection</pre>");
}

if (!file_exists(ROOT . 'config' . DS . 'app.config.php')) {
    die("<pre>Rename 'config/app.config.php.install' to 'config/app.config.php' and configure your application</pre>");
}

require_once VENDORDIR . "autoload.php";

include(SRCDIR . "appSlim.php");

//initialize Slim
$app = (new src\appSlim\appSlim(ROOT, DS))->getAppSlim();
$settings = include ROOT . "config" . DS . 'app.config.php';
$app->add(new \RKA\SessionMiddleware(['name' => 'roadinfo_backID', 'lifetime' => 3600*5]));

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $req->getServerParam('HTTP_ORIGIN'))
        //->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Authorization, X-Requested-With, Origin, Credentials');
});

$scan = glob(SRCDIR . "*" . DS . "*",GLOB_ONLYDIR);
foreach ($scan as $router) {
    if (strpos($router, DS . 'routes', -0) > 0){
        foreach (glob($router . DS .'*.php') as $file) {
            require_once $file;
        }
    }
}

$app->run();

