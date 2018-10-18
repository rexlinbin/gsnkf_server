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
	exit 1;
}

ARGV=($(getopt -o i:u:p -- "$@"))

group_id_list=
user=
password=
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

MYSQL_EXEC="$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD "

MIN_TIME=`date -d -1day +%s`
sql="select count(*) from t_hero where uid = 0 and upgrade_time > $MIN_TIME and delete_time >= $MIN_TIME"

for i in `echo $group_id_list | tr ';' ' '`; do
	db_ip=`getSlaveDBIP $i`;
	db_name=`getSimpleDBName $i`
	
	if [ -z "$db_ip" ];then
		echo_error "getSlaveDBIP failed";
		exit 1
	fi
	
	ret=`$MYSQL_EXEC -h $db_ip pirate$db_name -N -e "$sql"`
	if [ $ret -gt 0 ];then
		echo_info "$db_name hid marked"
		continue
	fi
	
	main_ip=`getLcserverIp $i`
	scp $BASEPATH/script/SetIdMark.php  $main_ip:$RPCFWPATH/script/
	if [ $? -eq 255 ]; then
	    echo_error "scp $main_ip failed!";
	    exit 1;
	fi
	
	ssh $main_ip "cd $RPCFWPATH/script && /home/pirate/bin/btscript game$i SetIdMark.php "
	if [ $? -eq 255 ]; then
		echo_error "setIdMark failed";
		exit 1;
	fi
	
	sleep 1
	
	ret=`$MYSQL_EXEC -h $db_ip pirate$db_name -N -e "$sql"`
	if [ $ret -le 0 ];then
		echo_error "$db_name hid not marked"
		exit 1
	fi
done


exit 0;