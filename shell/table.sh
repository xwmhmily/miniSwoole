table=""
#设置行，可以是表头，也可以是表格内容。
#如果是表格内容，“—”表示空值
function setRow(){
    value=$*
    table=${table}"|${value// /#|}#|\n"
}

#行分隔线
#入参：表格的列数。如表格有5列，则入参为5
function splitLine(){
    local num=`expr $1 + 2`
    split=`seq -s '+#' $num | sed 's/[0-9]//g'`    # 生成连续个的+#
    table=${table}"${split}\n"
}

#绘制表格
#入参：table
function setTable(){
    echo -e $1|column -s "#" -t|awk '{if($0 ~ /^+/){gsub(" ","-",$0);print $0}else{print $0}}'
}