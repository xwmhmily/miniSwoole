#!/bin/bash
# start, stop, status, restart, reload server
# Usage: sh socket.sh {start|stop|restart|status|reload}

PHP="/usr/bin/php"
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

cat logo.txt

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
    # yum install -y jq
    SWOOLE_MASTER_PID=`cat $PID_FILE`
    if [ $SWOOLE_MASTER_PID ]; then
        awk 'BEGIN{OFS="=";NF=58;print}'
        STAT_FILE="/var/log/app/mini_swoole_stat.log"
        
        server=`cat $STAT_FILE | jq '.server' | sed 's/"//g'`
        echo -e "\nServer: "$server

        masterPID=`cat $STAT_FILE | jq '.masterPID' | sed 's/"//g'`
        echo 'MasterPID: '$masterPID

        swoole_verser=`cat $STAT_FILE | jq '.swoole_version' | sed 's/"//g'`
        echo 'Swoole_version: '$swoole_verser
        awk 'BEGIN{OFS="=";NF=24;print}'

        echo -e "\nStats: "
        awk 'BEGIN{OFS="=";NF=32;print}'

        start_time=`cat $STAT_FILE | jq '.stats' | jq '.start_time' | sed 's/"//g'`
        echo -e "start_time: "$start_time

        worker_request_count=`cat $STAT_FILE | jq '.stats' | jq '.worker_request_count'`
        echo -e "worker_request_count: "$worker_request_count

        request_count=`cat $STAT_FILE | jq '.stats' | jq '.request_count'`
        echo -e "request_count: "$request_count

        tasking_num=`cat $STAT_FILE | jq '.stats' | jq '.tasking_num'`
        echo -e "tasking_num: "$tasking_num

        close_count=`cat $STAT_FILE | jq '.stats' | jq '.close_count'`
        echo -e "close_count: "$close_count

        accept_count=`cat $STAT_FILE | jq '.stats' | jq '.accept_count'`
        echo -e "accept_count: "$accept_count

        connection_num=`cat $STAT_FILE | jq '.stats' | jq '.connection_num'`
        echo -e "connection_num: "$connection_num

        awk 'BEGIN{OFS="=";NF=32;print}'

        echo -e "\nPorts: "
        awk 'BEGIN{OFS="=";NF=130;print}'

        ports=`cat $STAT_FILE | jq -r '.ports' | sed 's/"//g'`
        echo $ports
        awk 'BEGIN{OFS="=";NF=130;print}'

        echo -e "\nProcesses: "
        awk 'BEGIN{OFS="=";NF=130;print}'
        ps aux | grep Mini_Swoole | grep -v grep
        awk 'BEGIN{OFS="=";NF=130;print}'
    else
        TIP="Server is DOWN !!!"
    	MSG=${RED_COLOR}${TIP}${RES}
        echo -e $MSG
    fi    
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