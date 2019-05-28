#!/bin/bash
# start, stop, status, restart, reload server
# Usage: sh socket.sh {start|stop|restart|status|reload|config}

PHP="/usr/bin/php"
CUR_DATE=`date +%F`
TIME=`date "+%Y-%m-%d %H:%M:%S"`
PARENT_PATH=$(dirname "$PWD")
PHP_FILE="/Boostrap.php"
PID_FILE=../pid/swoole.pid
SOCKET_FILE="$PARENT_PATH$PHP_FILE"
LOG_FILE=/var/log/app/ts_swoole_$CUR_DATE.log
STAT_FILE="/var/log/app/mini_swoole_stat.log"

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
        . ./table.sh
        app=`cat $STAT_FILE | jq '.app' | sed 's/"//g'`
        server=`cat $STAT_FILE | jq '.server' | sed 's/"//g'`
        masterPID=`cat $STAT_FILE | jq '.masterPID' | sed 's/"//g'`
        php_version=`cat $STAT_FILE | jq '.php_version' | sed 's/"//g'`
        swoole_verion=`cat $STAT_FILE | jq '.swoole_version' | sed 's/"//g'`

        echo -e "\nBasic: "
        table=""
        splitLine 5
        setRow "App" "Server" "MasterPID" "PHP" "Swoole"
        splitLine 5
        setRow $app $server $masterPID $php_version $swoole_verion
        splitLine 5
        setTable ${table}

        echo -e "\nStats: "
        start_time=`cat $STAT_FILE | jq '.stats' | jq '.start_time' | sed 's/"//g'`
        worker_request_count=`cat $STAT_FILE | jq '.stats' | jq '.worker_request_count'`
        request_count=`cat $STAT_FILE | jq '.stats' | jq '.request_count'`
        tasking_num=`cat $STAT_FILE | jq '.stats' | jq '.tasking_num'`
        close_count=`cat $STAT_FILE | jq '.stats' | jq '.close_count'`
        accept_count=`cat $STAT_FILE | jq '.stats' | jq '.accept_count'`
        connection_num=`cat $STAT_FILE | jq '.stats' | jq '.connection_num'`

        table=""
        splitLine 8
        setRow "start_data" "start_time" "worker_request_count" "request_count" "tasking_num" "close_count" "accept_count" "connection_num"
        splitLine 8
        setRow $start_time $worker_request_count $request_count $tasking_num $close_count $accept_count $connection_num
        splitLine 8
        setTable ${table}

        echo -e "\nPorts: "
        awk 'BEGIN{OFS="=";NF=130;print}'

        ports=`cat $STAT_FILE | jq -r '.ports' | sed 's/"//g'`
        echo $ports
        awk 'BEGIN{OFS="=";NF=130;print}'

        echo -e "\nProcesses: "
        awk 'BEGIN{OFS="=";NF=130;print}'
        ps aux | grep miniSwoole | grep -v grep
        awk 'BEGIN{OFS="=";NF=130;print}'
    else
        TIP="Server is DOWN !!!"
    	MSG=${RED_COLOR}${TIP}${RES}
        echo -e $MSG
    fi    
}

config() {
    config=`cat $STAT_FILE | jq '.config' | sed 's/"//g'`

    echo -e "\nConfig: "
    awk 'BEGIN{OFS="=";NF=172;print}'
    echo $config
    awk 'BEGIN{OFS="=";NF=172;print}'
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
    config)
        config
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|reload|config}"
        ;;
esac
exit 0