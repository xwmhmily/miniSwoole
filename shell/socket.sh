#!/bin/bash
# start, stop, status, restart, reload server
# Usage: sh socket.sh {start|stop|restart|status|reload}

PHP=`which php`
CUR_DATE=`date +%F`
TIME=`date "+%Y-%m-%d %H:%M:%S"`
PARENT_PATH=$(dirname "$PWD")
PHP_FILE="/Boostrap.php"
PID_FILE=../pid/swoole.pid
SOCKET_FILE="$PARENT_PATH$PHP_FILE"
LOG_FILE=/var/log/app/ts_swoole_$CUR_DATE.log

#定义颜色的变量
RED_COLOR='\E[1;31m'   #红
GREEN_COLOR='\E[1;32m' #绿
YELOW_COLOR='\E[1;33m' #黄
BLUE_COLOR='\E[1;34m'  #蓝
PINK='\E[1;35m'        #粉红
RES='\E[0m'

start() {
    SWOOLE_MASTER_PID=`cat $PID_FILE`
    if [ $SWOOLE_MASTER_PID ]; then
        echo 'Server is running ......' && exit 0
    else
        echo 'Starting ......'
        touch $LOG_FILE && chown www.www $LOG_FILE && chmod 777 $LOG_FILE
        $PHP $SOCKET_FILE &
        sleep 1
        NEW_SWOOLE_MASTER_PID=`cat $PID_FILE`
        if [ $NEW_SWOOLE_MASTER_PID ]; then
            TIP="Server start success"
        	MSG=${GREEN_COLOR}${TIP}${RES}
        else
        	TIP="Server start fail"
            MSG=${GREEN_COLOR}${TIP}${RES}
        fi
    fi

    echo -e $MSG && echo $TIME "|" $TIP >> $LOG_FILE
}

stop() {
    echo 'Stopping ......'
    ### DO NOT USE KILL -9, GIVE THE MASTER CHANCE TO DO SOMETHING ###
    SWOOLE_MASTER_PID=`cat $PID_FILE`
    kill -15 $SWOOLE_MASTER_PID
    sleep 1
    NEW_SWOOLE_MASTER_PID=`ps -ef | grep ${SWOOLE_MASTER_PID} | grep -v "grep" | sed -n '1p' | awk -F ' ' '{print $2}'`
    if [ $NEW_SWOOLE_MASTER_PID ]; then
        TIP="Server stop fail !!!"
    	MSG=${RED_COLOR}${TIP}${RES}
    else
        TIP="Server stop success"
        MSG=${GREEN_COLOR}${TIP}${RES}
        > $PID_FILE
    fi

    echo -e $MSG && echo $TIME "|" $TIP >> $LOG_FILE
}

restart() {
    stop
    sleep 1
    start
}

reload() {
    SWOOLE_MASTER_PID=`cat $PID_FILE`
    MSG=' Reloading... '
    kill -USR1 $SWOOLE_MASTER_PID
    echo $MSG
}

status() {
    SWOOLE_MASTER_PID=`cat $PID_FILE`
    if [ $SWOOLE_MASTER_PID ]; then
        TIP="Server with pid ${SWOOLE_MASTER_PID} is running"
    	MSG=${GREEN_COLOR}${TIP}${RES}
    else
        TIP="Server is DOWN !!!"
    	MSG=${RED_COLOR}${TIP}${RES}
    fi

    echo -e $MSG && echo $TIME "|" $TIP >> $LOG_FILE
}

case "$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    status)
        status
        ;;
    reload)
        reload
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|reload}"
        ;;
esac
exit 0