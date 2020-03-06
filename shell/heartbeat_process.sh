#!/bin/bash -x
# Mini_Swoole heartbeat process

PHP=`which php`
PARENT_PATH=$(dirname "$PWD")
PHP_FILE="/Heartbeat.php"

$PHP $PARENT_PATH$PHP_FILE
echo 'DONE'