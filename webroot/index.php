<?php
date_default_timezone_set('PRC');
//自定义打印函数
if ( ! function_exists('dd')) {
    function dd()
    {
        $args = func_get_args();
        foreach ($args as $val) {
            echo '<pre style="color: red">';
            var_dump($val);
            echo '</pre>';
        }
        die;
    }
}
define('XHGUI_ROOT_DIR', dirname(__DIR__));

if (file_exists(XHGUI_ROOT_DIR . '/vendor/autoload.php')) {
    require XHGUI_ROOT_DIR . '/vendor/autoload.php';
} elseif (file_exists(XHGUI_ROOT_DIR . '/../../autoload.php')) {
    require XHGUI_ROOT_DIR . '/../../autoload.php';
}



require dirname(__DIR__) . '/src/bootstrap.php';

$di = new Xhgui_ServiceContainer();

$app = $di['app'];

require XHGUI_ROOT_DIR . '/src/routes.php';

$app->run();
