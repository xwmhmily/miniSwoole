<?php

define('APP_PATH', realpath(__DIR__));

require APP_PATH.'/Env.php';
require APP_PATH.'/library/core/MiniSwoole.php';

$miniSwoole = new MiniSwoole();

if(isset($argv[1])){
    $argv = $argv[1];
}

if(!$argv || $argv != 'process'){
    $miniSwoole->boostrap()->run();
}else{
    $miniSwoole->boostrap()->process();
}