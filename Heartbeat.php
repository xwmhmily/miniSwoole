<?php

define('APP_PATH', realpath(__DIR__));
require APP_PATH.'/Env.php';
require APP_PATH.'/TinySwoole.php';

$tinySwoole = new TinySwoole();
$tinySwoole->boostrap()->heartbeat();