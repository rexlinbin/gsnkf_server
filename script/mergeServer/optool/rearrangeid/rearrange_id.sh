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
	echo "-x data.xml";
	exit 1;
}

ARGV=($(getopt -o g:u:p:x: -- "$@"))

group_id=
user=
password=
dataxmlpath=

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
   		-x)
			((i++));
			eval dataxmlpath=${ARGV[$i]}
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

if [ -z $dataxmlpath ]; then
	echo "-x should be set!";
	print_use;
fi
echo $dataxmlpath


sdb_ip=`getSlaveDBIP $group_id`
mdb_ip=`getMasterDBIP $group_id`
db_name=`getSimpleDBName $group_id`
tmp_db_name="id_${db_name}"

MYSQL_EXEC="$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD "


$MYSQL_EXEC -h $mdb_ip -e "create database if not exists pirate$tmp_db_name"
$MYSQL_EXEC -h $mdb_ip pirate$tmp_db_name < $BASEPATH/doc/tmp_user.sql
$MYSQL_EXEC -h $mdb_ip pirate$tmp_db_name < $BASEPATH/doc/tmp_id.sql


$MYSQL_EXEC -h $mdb_ip -e "create table pirate${tmp_db_name}.t_hero like pirate${db_name}.t_hero"

for t in t_item t_bag; do

	for t in `$MYSQL_EXEC -h $mdb_ip pirate$db_name -N -e "show tables like '${t}_%'"`; do

		sql="create table pirate${tmp_db_name}.${t} like pirate${db_name}.${t}"
		echo "$sql"
		$MYSQL_EXEC -h $mdb_ip -e "$sql"
	done
done



$PHPPATH/bin/php $BASEPATH/optool/rearrangeid/index.php --mf $db_name --md $sdb_ip \
		--tg $tmp_db_name --td $mdb_ip \
		-u $user -p $password -x $dataxmlpath
			

for t in t_item t_bag; do

	for table in `$MYSQL_EXEC -h $mdb_ip pirate$db_name -N -e "show tables like '${t}_%'"`; do
		bak_table="bak_${table}"
		sql="rename table pirate${db_name}.${table} to pirate${tmp_db_name}.${bak_table}"
		echo "$sql"
		$MYSQL_EXEC -h $mdb_ip -e "$sql"
	done
	
	for table in `$MYSQL_EXEC -h $mdb_ip pirate$tmp_db_name -N -e "show tables like '${t}_%'"`; do
		sql="rename table pirate${tmp_db_name}.${table} to pirate${db_name}.${table}"
		echo "$sql"
		$MYSQL_EXEC -h $mdb_ip -e "$sql"
	done
	
done

table="t_hero"
bak_table="bak_${table}"
$MYSQL_EXEC -h $mdb_ip -e "rename table pirate${db_name}.${table} to pirate${tmp_db_name}.${bak_table}"
$MYSQL_EXEC -h $mdb_ip -e "rename table pirate${tmp_db_name}.${table} to pirate${db_name}.${table}"


#rename 一下临时库
from="pirate${tmp_db_name}"
to="tmp${tmp_db_name}"
$MYSQL_EXEC -h $mdb_ip -e "create database $to"
ret=$?
if [ $ret -ne 0 ]; then
    echo "create database $to failed, check mysql for sure"
    exit 1
fi

for table in `$MYSQL_EXEC -h $mdb_ip -N -e "show tables from $from" `; do
    $MYSQL_EXEC -h $mdb_ip -e "rename table $from.$table to $to.$table"
    ret=$?
    if [ $ret -ne 0 ]; then
        echo "rename table $table failed"
        exit 1
    fi
done

num=`$MYSQL_EXEC -h $mdb_ip -e "show tables from $from"|wc -l`
if [ $num -ne 0 ]; then
    echo "database $from is not empty"
    exit 1
fi

$MYSQL_EXEC -h $mdb_ip -e "drop database $from"
ret=$?
if [ $ret -ne 0 ]; then
    echo "drop database $from failed"
    exit 1
fi

exit 0;

