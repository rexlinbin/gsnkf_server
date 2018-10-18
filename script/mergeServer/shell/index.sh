#!/bin/bash

#得到当前文件所在目录
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

source $BASEPATH/shell/config.sh
source $BASEPATH/optool/dbLcserverInfo.sh

#打印帮助
print_use()
{
	echo;
	echo "USAGE:";
	echo "-i merge server ids, split by ';'; eg:002;003";
	echo "-u db user";
	echo "-p db password";
	echo "-d target db ip";
	echo "-t target server id";
	echo "-x dataproxy xml path; default /home/pirate/rpcfw/data/dataproxy.xml";
	echo "-m multi process num; default 1";
	exit 1;
}

#移除临时文件
rmtmpfile()
{
local SCRIPT_PATH=$BASEPATH/tmp
rm -f $SCRIPT_PATH/id$TIME
rm -f $SCRIPT_PATH/sortid$TIME
rm -f $SCRIPT_PATH/db$TIME
}

ARGV=($(getopt -o x:i:u:p:d:t:m: -- "$@"))

SCRIPT_PATH=$BASEPATH/tmp
TIME=`date +%Y%m%d%H%M%S`
TMP_ID_FILE=$SCRIPT_PATH/id$TIME
TMP_ID_SORT_FILE=$SCRIPT_PATH/sortid$TIME
TMP_DB_FILE=$SCRIPT_PATH/db$TIME

ids=
user=
password=
targetdb=
targetid=
multi_process=2
dataxmlpath=/home/pirate/rpcfw/data/dataproxy.xml

#处理输入参数
for((i = 0; i < ${#ARGV[@]}; i++)) {
	eval opt=${ARGV[$i]}
	case $opt in
		-i)
			((i++));
   			eval ids=${ARGV[$i]};
   			;;
		-u)
			((i++));
			eval user=${ARGV[$i]}
   			;;
		-p)
			((i++));
			eval password=${ARGV[$i]}
   			;;
		-d)
			((i++));
			eval targetdb=${ARGV[$i]}
				;;
		-t)
			((i++));
			eval targetid=${ARGV[$i]}
				;;
		-m) 
			((i++));
			eval multi_process=${ARGV[$i]}
				;;
		-x) 
			((i++));
			eval dataxmlpath=${ARGV[$i]}
				;;			
		--)
			break
				;;
	esac
}

#检测是否ids参数被设置
if [ -z $ids ]; then
	echo "-i should be set!";
	print_use;
fi

#检测user参数是否被设置
if [ -z $user ]; then
	echo "-u should be set!";
	print_use;
fi

#检测password是否被设置
if [ -z $password ]; then
	echo "-p should be set!";
	print_use;
fi

#检测targetdb是否被设置
if [ -z $targetdb ]; then
	echo "-d should be set!";
	print_use;
fi

#检测targetid是否被设置
if [ -z $targetid ]; then
	echo "-t should be set!";
	print_use;
fi

#处理ids
> $TMP_ID_FILE
for i in `echo $ids | tr ';' ' '`; do
	echo $i >> $TMP_ID_FILE
done

for i in `cat $TMP_ID_FILE`; do
	db=`getSlaveDBIP $i`;
	if [ -z $db ]; then
		echo "EXIT!game $i db info not found!";
		exit 1;
	fi
	echo $db >> $TMP_DB_FILE
	gamefirst=$i
	gamefirstdb=$db
done

#检测serverids数组是否为空或者长度为1
if [ `cat $TMP_DB_FILE | wc -l` -le 1 ]; then
	echo "merge server must two or more";
	rmtmpfile;
	print_use;
fi
 
PHP=$PHPPATH/bin/php

echo "EXPORT FIRST DB TABLE STRUCT!";
sh $BASEPATH/shell/export.sh $user $password $gamefirstdb pirate$gamefirst $SCRIPT_PATH/tmp_pirate.sql
echo "EXPORT FIRST DB TABLE STRUCT DONE!";


echo "IMPORT TARGET DB TABLE STRUCT AND USE MODIFY SQL!"
sh $BASEPATH/shell/import.sh $user $password $targetdb pirate$targetid $SCRIPT_PATH/tmp_pirate.sql
msg=$?
if [ $msg -eq 1 ]; then
	echo "MERGE SERVER EXIT! BECAUSE TARGET DB EXIST!";
	rmtmpfile;
	exit 1;
fi
echo "IMPORT TARGET DB TABLE STRUCT AND USE MODIFY SQL DONE!";

echo "MERGE SERVER $ids TO $targetid!";
$PHP $BASEPATH/index.php --mf $TMP_ID_FILE \
	--md $TMP_DB_FILE --td $targetdb --tg $targetid --mp $multi_process \
	-u $user -p $password -x $dataxmlpath
msg=$?
if [ $msg -ne 0 ]; then
	echo "MERGE SERVER FAILED!"
	rmtmpfile;
	exit 1;
fi
echo "MERGE SERVER $ids TO $targetid DONE!";

echo "SUFFIX SQL DEAL START!";
/bin/bash $BASEPATH/shell/suffix.sh $user $password $targetdb pirate$targetid
echo "SUFFIX SQL DEAL DONE!";

rmtmpfile;
echo "SUCCESS!MERGE DONE!";
mkdir -p $BASEPATH/tmp/done
touch $BASEPATH/tmp/done/$targetid