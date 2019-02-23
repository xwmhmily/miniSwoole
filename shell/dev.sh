#!/bin/bash -x
# 开发的时候要手动运行这脚本来实现代码的更新与服务的重启

git pull && sh socket.sh restart