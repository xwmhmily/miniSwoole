#!/bin/bash
# Heartbeat the process
# Usage: sh process.sh

PHP=`which php`
PARENT_PATH=$(dirname "$PWD")
PROCESS_HEARTBEAT_FILE=$PARENT_PATH"/Boostrap.php"

cat logo.txt && $PHP $PROCESS_HEARTBEAT_FILE process