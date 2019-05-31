#!/bin/bash

#定义颜色的变量
RED_COLOR='\E[1;31m'   #红
GREEN_COLOR='\E[1;32m' #绿
YELOW_COLOR='\E[1;33m' #黄
BLUE_COLOR='\E[1;34m'  #蓝
PINK='\E[1;35m'        #粉红
RES='\E[0m'
PROCESS_NAME='Tiny_Swoole_importer'

PID_NUM=`ps -ef | grep $PROCESS_NAME | grep -v "grep" | wc -l`

stop() {
	if [ $PID_NUM -eq 0 ]; then
		echo $PROCESS_NAME' is not running'
	else
		echo 'Stopping '$PROCESS_NAME'......'
		PID_TO_KILL=`ps -ef | grep $PROCESS_NAME | grep -v "grep" | awk -F ' ' '{print $2}'`
		for pid in $PID_TO_KILL
		do
			kill $pid
		done

        sleep 1

		NEW_PID_NUM=`ps -ef | grep $PROCESS_NAME | grep -v "grep" | wc -l`
		if [ $NEW_PID_NUM -eq 0 ]; then
			TIP=$PROCESS_NAME" stop success"
			MSG=${GREEN_COLOR}${TIP}${RES}
		else
			TIP=$PROCESS_NAME" stop fail"
			MSG=${GREEN_COLOR}${TIP}${RES}
		fi

		echo -e $MSG && PID_NUM=0
	fi
}

status() {
    if [ $PID_NUM -gt 0 ]; then
        TIP=$PROCESS_NAME" with ${PID_NUM} process(es) is running"
    	MSG=${GREEN_COLOR}${TIP}${RES}
        echo -e $MSG
    else
        TIP=$PROCESS_NAME" is DOWN !"
        echo -e $TIP
    fi
}

case "$1" in
    stop)
        stop
        ;;
    *)
        status
        ;;
esac
exit 0