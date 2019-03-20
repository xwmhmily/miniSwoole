<?php

// DEV, UAT, PRODUCTION, change this value when upgrade
define('ENV', 'DEV');
define('APP_PATH', realpath(__DIR__));
date_default_timezone_set('Asia/Chongqing');

require APP_PATH.'/MiniSwoole.php';

$miniSwoole = new MiniSwoole();
$miniSwoole->boostrap()->init()->config()->run();