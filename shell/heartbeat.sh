#!/bin/bash -x
# miniSwoole heartbeat
# Usage: sh heartbeart.sh

RES='\E[0m'
GREEN_COLOR='\E[1;32m' #绿
PID_FILE=../pid/swoole.pid
SWOOLE_MASTER_PID=`cat $PID_FILE`
NEW_SWOOLE_MASTER_PID=`ps -ef | grep ${SWOOLE_MASTER_PID} | grep -v "grep" | sed -n '1p' | awk -F ' ' '{print $2}'`

cd /usr/www/miniSwoole/shell

if [ ! $NEW_SWOOLE_MASTER_PID ]; then
	sh socket.sh restart
else
	cat logo.txt
	echo -e "${GREEN_COLOR}Server with PID ${SWOOLE_MASTER_PID} is running ${RES}"
fi