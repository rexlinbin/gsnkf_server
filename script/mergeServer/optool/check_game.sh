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
	echo "-x data.xml";
	exit 1;
}

ARGV=($(getopt -o x:i:u:p:d:t:m -- "$@"))

group_id_list=
user=
password=
dataxmlpath=
#处理输入参数
for((i = 0; i < ${#ARGV[@]}; i++)) {
	eval opt=${ARGV[$i]}
	case $opt in
		-i)
			((i++));
   			eval group_id_list=${ARGV[$i]};
   			;;
		-u)
			((i++));
			eval user=${ARGV[$i]}
   			;;
		-p)
			((i++));
			eval password=${ARGV[$i]}
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

#检测是否group_id_list参数被设置
if [ -z $group_id_list ]; then
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

if [ -z $dataxmlpath ]; then
	echo "-x should be set!";
	print_use;
fi


game_info_list=
for i in `echo $group_id_list | tr ';' ' '`; do
	db_ip=`getSlaveDBIP $i`;
	db_name=`getSimpleDBName $i`
	game_info_list="${game_info_list}${i}|${db_ip}|${db_name};"
done



$PHPPATH/bin/php $BASEPATH/script/CheckGame.php --gl $game_info_list -u $user -p $password -x $dataxmlpath
msg=$?
if [ $msg -ne 0 ]; then
	echo "CHECK GAME FAILED!"
	exit 1;
fi

exit 0;