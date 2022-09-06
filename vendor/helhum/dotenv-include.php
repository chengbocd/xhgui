<?php
call_user_func(static function($dotEnvFile) {
    $class = \Helhum\DotEnvConnector\Adapter\SymfonyDotEnv::class;
    (new $class())->exposeToEnvironment($dotEnvFile);
}, dirname(dirname(__DIR__)).'/.env');
