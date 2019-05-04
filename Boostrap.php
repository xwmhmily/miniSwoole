<?php

// DEV, UAT, PRODUCTION, change this value when upgrade
define('ENV', 'DEV');
define('APP_PATH', realpath(__DIR__));

require APP_PATH.'/MiniSwoole.php';

$miniSwoole = new MiniSwoole();
$miniSwoole->boostrap()->run();