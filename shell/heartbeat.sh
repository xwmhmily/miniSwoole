#!/bin/bash -x
# Mini_Swoole_tcp_master heartbeat

RES='\E[0m'
GREEN_COLOR='\E[1;32m' #绿
PID_FILE=../pid/swoole.pid
SWOOLE_MASTER_PID=`cat $PID_FILE`
NEW_SWOOLE_MASTER_PID=`ps -ef | grep ${SWOOLE_MASTER_PID} | grep -v "grep" | sed -n '1p' | awk -F ' ' '{print $2}'`

if [ ! $NEW_SWOOLE_MASTER_PID ]; then
	sh socket.sh restart
else
	echo -e "${GREEN_COLOR}Server with PID ${SWOOLE_MASTER_PID} is running ${RES}"
fi