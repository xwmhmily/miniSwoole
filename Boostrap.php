<?php

define('APP_PATH', realpath(__DIR__));
require APP_PATH.'/Env.php';
require APP_PATH.'/MiniSwoole.php';

$miniSwoole = new MiniSwoole();
$miniSwoole->boostrap()->run();