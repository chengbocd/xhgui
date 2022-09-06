<?php
/**
 * Default configuration for Xhgui
 */

return array(
    'debug' => false,
    'mode' => 'development',
    /*
     * support extension: uprofiler, tideways_xhprof, tideways, xhprof
     * default: xhprof
     */
    'extension' => 'tideways',

    // Can be either mongodb or file.
    /*
    'save.handler' => 'file',
    'save.handler.filename' => dirname(__DIR__) . '/cache/' . 'xhgui.data.' . microtime(true) . '_' . substr(md5($url), 0, 6),
    */
    'save.handler' => 'mongodb',

    // Needed for file save handler. Beware of file locking. You can adujst this file path
    // to reduce locking problems (eg uniqid, time ...)
    //'save.handler.filename' => __DIR__.'/../data/xhgui_'.date('Ymd').'.dat',
    // 'db.host' => $a['XHGUI_MONGO_URI'] ?? 'mongodb://127.0.0.2:27017',
    // 'db.db' => $a['XHGUI_MONGO_DB'] ?? 'xhprof',
    //test3
    'db.host' => 'mongodb://10.10.32.20:27017',
    'db.db' => 'xhprof3',

    // Allows you to pass additional options like replicaSet to MongoClient.
    // 'username', 'password' and 'db' (where the user is added)
    'db.options' => array(),
    'templates.path' => dirname(__DIR__) . '/src/templates',
    'date.format' => 'Y-m-d H:i:s',
    'detail.count' => 6,
    'page.limit' => 25,

    // Profile 1 in 100 requests.
    // You can return true to profile every request.
    'profiler.enable' => function() {
        // return true;
        return rand(1, 100) === 210;//默认当前框架运行 不收集性能数据
    },

    'profiler.simple_url' => function($url) {
        return preg_replace('/\=\d+/', '', $url);
    },

    'profiler.filter_path' => array(
        //'/home/admin/www/xhgui/webroot','F:/phpPro'
    )

);
