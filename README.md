### xhgui

##### 参考项目地址

> https://github.com/laynefyc/xhgui-branch
>
> 如果是单个项目直接nginx 修改下完事，对于多项目可以参考次项目

##### mongo数据加索引

~~~
db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
db.results.ensureIndex( { 'profile.main().wt' : -1 } )
db.results.ensureIndex( { 'profile.main().mu' : -1 } )
db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
db.results.ensureIndex( { 'meta.url' : 1 } )
db.results.ensureIndex( { 'project' : 1 } )
~~~

##### 安装扩展

```php
   sudo pecl install mongodb
   PHP 配置文件中添加
   extension=mongodb.so
   tideways建议4.1.7 能记录sql 建议装这个太高不太行
   下载
   git clone https://github.com/tideways/php-xhprof-extension/tree/v4.1.7 
   cd /path/php-xhprof-extension
   phpize
   ./configure
   make
   sudo make install
   PHP 配置文件中添加、
   [tideways]
   extension=tideways.so
   ; 不需要自动加载，在程序中控制就行
   tideways.auto_prepend_library=0
   ; 频率设置为100，在程序调用时可以修改
   tideways.sample_rate=100
   记得重启php-fpm
```

#### laravel框架接入

```php
    composer require guangzhonghedd01/xhgui-collector  //收集数据
    composer require alcaeus/mongo-php-adapter //链接mongodb 扩展
    对应的php扩展安装
   
    #.env 配置文件添加
    XHGUI_MONGO_URI=10.10.32.20:27017//mogodb 地址10.10.32.20:27017 3套环境都用这个 选择不同的库
    XHGUI_MONGO_DB=xhprof //dev test123  xhprofdev xhprof1 xhprof2 xhprof3
    XHGUI_PROFILING_RATIO=100//收集比率 100代表100% 1代表1% 100次请求记录1次概率
    XHGUI_EXECUTE_SECOND=0.5//响应时间 大于这个时间的才会被手机
    XHGUI_PROFILING=enabled//开启收集 
    XHGUI_FILTER_VAR="env"
    APP_NAME=cms// 项目名称 ehr xinchou laravel-order door 
    
    #config文件 下添加配置文件
    <?php
    /**
    * Default configuration for Xhgui
    */
    
    return [
        'debug'           => env('APP_DEBUG', false),
        'mode'            => env('APP_NAME', 'test'),
        'filter_var'      => empty(env('XHGUI_FILTER_VAR', '')) ?
            [] : explode(',', env('XHGUI_FILTER_VAR')),
        'save.handler'    => 'mongodb',
        'db.host'         => sprintf('mongodb://%s', env('XHGUI_MONGO_URI', '127.0.0.1:27017')),//mongodb 数据
        'db.db'           => env('XHGUI_MONGO_DB', 'xhprof'),//mongodb 数据
        // Allows you to pass additional options like replicaSet to MongoClient.
        // 'username', 'password' and 'db' (where the user is added)
        'db.options'      => ['ssl' => in_array(env('APP_NAME'), ['stg', 'prod']) ? true : false],
        'templates.path'  => dirname(__DIR__) . '/src/templates',
        'date.format'     => 'M jS H:i:s',
        'detail.count'    => 6,
        'page.limit'      => 25,
        'profiler.enable' => (int)env('XHGUI_PROFILING_RATIO', 0),//采集开关  采集率
        'profiler.options' => ['extension'],
        'profiler.second' => env('XHGUI_EXECUTE_SECOND', 0),// 采集执行时间大于这个的数  默认0  建议设置成0.5秒
    ];
    
    #添加中间件
    App\Http;     Kernel下
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \App\Http\Middleware\Cors::class,
        \App\Http\Middleware\OperationLog::class,
        \App\Http\Middleware\Xhprof::class,//添加这行代码
    ];
```

###  laravel框架  
> app-Middleware 添加两文件 Xhprof.php ImportXhgui.php 下面是Xhprof.php
```php
<?php

namespace App\Http\Middleware;

use Closure;

class Xhprof
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        ImportXhgui::laravel();

        return $next($request);
    }

}
```

### ImportXhgui.php
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Guangzhong\Xhgui\Config;
use Guangzhong\Xhgui\Import;
use Guangzhong\Xhgui\Saver;
use Guangzhong\Xhgui\Util;

class ImportXhgui extends Import
{
    public function __construct()
    {
        if (!\extension_loaded('xhprof')
            && !\extension_loaded('uprofiler')
            && !\extension_loaded('tideways')
            && !\extension_loaded('tideways_xhprof')
        ) {
            error_log('xhgui - either extension xhprof, uprofiler, tideways or tideways_xhprof must be loaded');

            return;
        }
    }

    /**
     * laravel 接入
     */
    public static function laravel()
    {
        Config::load(config('xhgui'));
        if ((!\extension_loaded('mongo')
                && !\extension_loaded('mongodb'))
            && Config::read('save.handler') === 'mongodb') {
            error_log('xhgui - extension mongo not loaded');

            return;
        }

        if (!Config::shouldRun()) {
            return;
        }

        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
        }
        $options = Config::read('profiler.options');

        if (\extension_loaded('uprofiler')) {
            uprofiler_enable(UPROFILER_FLAGS_CPU | UPROFILER_FLAGS_MEMORY, $options);
        } else if (\extension_loaded('tideways')) {
            tideways_enable(TIDEWAYS_FLAGS_CPU | TIDEWAYS_FLAGS_MEMORY);
            tideways_span_create('sql');
        } elseif (\extension_loaded('tideways_xhprof')) {
            tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);
        } else {
            if (PHP_MAJOR_VERSION === 5 && PHP_MINOR_VERSION > 4) {
                xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_NO_BUILTINS, $options);
            } else {
                xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, $options);
            }
        }

        register_shutdown_function(
            function () {
                if (\extension_loaded('uprofiler')) {
                    $data['profile'] = uprofiler_disable();
                } else if (\extension_loaded('tideways_xhprof')) {
                    $data['profile'] = tideways_xhprof_disable();
                } else if (\extension_loaded('tideways')) {
                    $data['profile'] = tideways_disable();
                    $sqlData = tideways_get_spans();
                    $data['sql'] = array();
                    if (isset($sqlData[1])) {
                        foreach ($sqlData as $val) {
                            if (isset($val['n']) && $val['n'] === 'sql' && isset($val['a']) && isset($val['a']['sql'])) {
                                $_time_tmp = (isset($val['b'][0]) && isset($val['e'][0])) ? ($val['e'][0] - $val['b'][0]) : 0;
                                if (!empty($val['a']['sql'])) {
                                    $data['sql'][] = array(
                                        'time' => $_time_tmp,
                                        'sql' => $val['a']['sql']
                                    );
                                }
                            }
                        }
                    }
                } else {
                    $data['profile'] = xhprof_disable();
                }

                if (!empty($data['profile'])){
                    $profile = [];
                    foreach($data['profile'] as $key => $value) {
                        $profile[strtr($key, ['.' => '_'])] = $value;
                    }

                    $data['profile'] = $profile;
                }

                // ignore_user_abort(true) allows your PHP script to continue executing, even if the user has terminated their request.
                // Further Reading: http://blog.preinheimer.com/index.php?/archives/248-When-does-a-user-abort.html
                // flush() asks PHP to send any data remaining in the output buffers. This is normally done when the script completes, but
                // since we're delaying that a bit by dealing with the xhprof stuff, we'll do it now to avoid making the user wait.
                ignore_user_abort(true);
                flush();

                $uri = array_key_exists('REQUEST_URI', $_SERVER)
                    ? $_SERVER['REQUEST_URI']
                    : null;
                if (empty($uri) && isset($_SERVER['argv'])) {
                    $cmd = basename($_SERVER['argv'][0]);
                    $uri = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
                }

                $time = array_key_exists('REQUEST_TIME', $_SERVER)
                    ? $_SERVER['REQUEST_TIME']
                    : time();

                // In some cases there is comma instead of dot
                $delimiter = (strpos($_SERVER['REQUEST_TIME_FLOAT'], ',') !== false) ? ',' : '.';
                $requestTimeFloat = explode($delimiter, $_SERVER['REQUEST_TIME_FLOAT']);
                if (!isset($requestTimeFloat[1])) {
                    $requestTimeFloat[1] = 0;
                }

                $requestTs = ['sec' => $time, 'usec' => 0];
                $requestTsMicro = ['sec' => $requestTimeFloat[0], 'usec' => $requestTimeFloat[1]];

                // 执行时间 转换成 有待测试
                $sec = round((microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'])*1000,2)/1000;
                // $sec = $main = $data['profile']['main()']['wt']/1000;dd();
                $set_time = Config::read('profiler.second');
                if ($sec < $set_time) {
                    return;
                }
                // if (isset($requestTsMicro['usec'])) {
                //     //执行时间转换成秒
                //     $sec = $requestTsMicro['usec'] / 1000000;
                //     $set_time = Config::read('profiler.second');
                //     if ($sec < $set_time) {
                //         return;
                //     }
                // }

                //过滤敏感数据信息，比如密码之类的
                $filterVar = Config::read('filter_var');
                foreach ($filterVar as $v) {
                    if (isset($_SERVER[$v])) {
                        unset($_SERVER[$v]);
                    }
                }

                $data['meta'] = [
                    'url' => $uri,
                    'SERVER' => $_SERVER,
                    'get' => $_GET,
                    // 'env'              => '', //去掉env信息
                    'simple_url' => Util::simpleUrl($uri),
                    'request_ts' => $requestTs,
                    'request_ts_micro' => $requestTsMicro,
                    'request_date' => date('Y-m-d', $time),
                ];

                $data['project'] = Config::read('mode');

                try {
                    $config = Config::all();
                    $config += ['db.options' => []];

                    $saver = Saver::factory($config);
                    $saver->save($data);

                } catch (\Exception $e) {
                    error_log('xhgui - ' . $e->getMessage());
                }
            }
        );
    }
}

```