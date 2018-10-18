#!/bin/bash

#得到当前文件所在目录
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

source $BASEPATH/shell/config.sh
source $BASEPATH/optool/dbLcserverInfo.sh

#打印帮助
print_use()
{
	echo;
	echo "USAGE:";
	echo "-g group id";
	echo "-u db user";
	echo "-p db password";
	exit 1;
}

ARGV=($(getopt -o g:u:p -- "$@"))

group_id=
user=
password=
#处理输入参数
for((i = 0; i < ${#ARGV[@]}; i++)) {
	eval opt=${ARGV[$i]}
	case $opt in
		-g)
			((i++));
   			eval group_id=${ARGV[$i]};
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

#检测是否group_id参数被设置
if [ -z $group_id ]; then
	echo "-g should be set!";
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

db_ip=`getSlaveDBIP $group_id`;
db_name=`getSimpleDBName $group_id`

max_item_id=0
for t in `$MYSQL_EXEC -h $db_ip pirate$db_name -N -e "show tables like 't_item_%'"`; do
	
	ret=`$MYSQL_EXEC -h $db_ip pirate$db_name -N -e "select max(item_id) from $t"`
	if [ "$ret"X != "NULLX" ];then
		if [ $max_item_id -lt $ret ];then
			max_item_id=$ret
		fi
	fi
done

echo "true max_item_id:$max_item_id"

max_item_id=$(( max_item_id / 100 * 100 + 200 ))

echo "after modify, max_item_id:$max_item_id"

main_ip=`getLcserverIp $group_id`

ssh $main_ip "cd $RPCFWPATH/script/tools && /home/pirate/bin/btscript game$group_id IdManager.php show item_id"

ssh $main_ip "cd $RPCFWPATH/script/tools && /home/pirate/bin/btscript game$group_id IdManager.php set item_id $max_item_id"
if [ $? -eq 255 ]; then
	echo_error "set id failed";
	exit 1;
fi

ret=`ssh $main_ip "cd $RPCFWPATH/script/tools && /home/pirate/bin/btscript game$group_id IdManager.php show item_id" | awk '{print $NF}' `

if [ $ret -ne $max_item_id ];then
	echo_error "set id failed. set:$max_item_id, cur:$ret";
	exit 1;
fi  


exit 0;

