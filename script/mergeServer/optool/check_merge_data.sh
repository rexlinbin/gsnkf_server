#!/bin/bash


#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/formatOutput.sh
source $BASEPATH/optool/dbLcserverInfo.sh

function usage()
{
	echo_usage "Usage:"
	echo_usage "	valid merge server config with group id"
	echo_usage "	sh $0 groupid"
}

if [ $# -lt 1 ]; then
	usage;
	exit 1;
fi

GROUP=$1

if [ `validGroupId $GROUP` -ne 1 ]; then
	echo_error "$GROUP is invalid";
	usage;
	exit 1;
fi


#得到从库地址
SIMPLEGROUP=`getSimpleDBName $GROUP`
SLAVEDB=`getSlaveDBIP $SIMPLEGROUP`

if [ -z $SLAVEDB ]; then
	echo "$GROUP slave db not exist!";
	exit 1;
fi

FIRSTGROUP=`getFirstGroupId $GROUP`
LCSERVERIP=`getLcserverIp $FIRSTGROUP`
LOGICIP=`getOneLogicIPByGroupId $LCSERVERIP $GROUP`
if [ -z $LOGICIP ]; then
	echo_error "getLogicIp by group id $GROUP failed!"
	exit 1;
fi

FIRST_GROUP_DB_NAME=`getSimpleDBName $FIRSTGROUP`
FIRST_GROUP_DB_HOSE=`getSlaveDBIP $FIRST_GROUP_DB_NAME`

#是否存在错误的标记
has_mistake=0

MYSQL_EXEC="$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD -h$SLAVEDB pirate$SIMPLEGROUP"
MYSQL_EXEC_FIRST_GROUP="$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD -h$FIRST_GROUP_DB_HOSE pirate$FIRST_GROUP_DB_NAME"

#检查t_tmp_user表是否为空
tmp_user_num=`$MYSQL_EXEC -e "select count(*) from t_tmp_user" | egrep -o '[0-9]{0,10}'`;
if [ $tmp_user_num -eq 0 ]; then
	echo_error "$GROUP t_tmp_user has error!";
	has_mistake=1;
fi

#检查t_tmp_user表的数量是否和user表相同
user_num=`$MYSQL_EXEC -e "select count(*) from t_user where uid >= 20000" | egrep -o '[0-9]{0,10}'`;
if [ $tmp_user_num -ne $user_num ]; then
	echo_error "$GROUP t_tmp_user num not equal t_user! has error!";
	has_mistake=1;
fi

#检查t_tmp_guild表的数量是否和t_guild表的数量相同
guild_num=`$MYSQL_EXEC -e "select count(*) from t_guild" | egrep -o '[0-9]{0,10}'`;
tmp_guild_num=`$MYSQL_EXEC -e "select count(*) from t_tmp_guild" | egrep -o '[0-9]{0,10}'`;
if [ $tmp_guild_num -ne $guild_num ]; then
	echo_error "$GROUP t_tmp_guild num not equal t_guild! has error!";
	has_mistake=1;
fi

#检查t_tmp_slim_user表的数量和t_slim_user表是否相同
slim_user_num=`$MYSQL_EXEC -e "select count(*) from t_slim_user where uid >= 20000" | egrep -o '[0-9]{0,10}'`;
tmp_slim_user_num=`$MYSQL_EXEC -e "select count(*) from t_tmp_slim_user" | egrep -o '[0-9]{0,10}'`;
if [ $tmp_slim_user_num -ne $slim_user_num ]; then
	echo_error "$GROUP t_tmp_slim_user num not equal t_slim_user! has error!";
	has_mistake=1;
fi

#检查boss的timer是否存在及数量是否正确
timer_num=`$MYSQL_EXEC -e "select count(*) from t_timer where execute_method like 'boss%' and status = 1" | egrep -o '[0-9]{0,10}'`;
if [ ! $timer_num -eq 1 ]; then
	echo_error "$GROUP boss timer has error!";
	has_mistake=1;
fi

#检查boss表的数据是否正确
boss_num=`$MYSQL_EXEC -e "select count(*) from t_boss" | egrep -o '[0-9]{0,10}'`;
if [ ! $boss_num -eq 1 ]; then
	echo_error "$GROUP boss num has error!";
	has_mistake=1;
fi

#检查boss的等级是否正确
boss_num=`$MYSQL_EXEC -e "select count(*) from t_boss where level = 1" | egrep -o '[0-9]{0,10}'`;
if [ ! $boss_num -eq 0 ]; then
	echo_error "$GROUP fixed boss data has error!";
	has_mistake=1;
fi

#检查mineral的数量是否正确
mineral_db_num=`$MYSQL_EXEC -e "select count(*) from t_mineral" | egrep -o '[0-9]{0,10}'`;
mineral_num=`grep -v SLEEP $BASEPATH/doc/suffix_mineral.sql | wc -l | egrep -o '[0-9]{0,10}'`;
if [ $mineral_db_num -ne $mineral_num  ]; then
	echo_error "$GROUP mineral has error!";
	has_mistake=1;
fi

#检查dart的数量是否正确
dart_db_num=`$MYSQL_EXEC -e "select count(*) from t_charge_dart_road" | egrep -o '[0-9]{0,10}'`;
dart_num=`grep -v SLEEP $BASEPATH/doc/suffix_dart.sql | wc -l | egrep -o '[0-9]{0,10}'`;
if [ $dart_db_num -ne $dart_num  ]; then
	echo_error "$GROUP dart has error!";
	has_mistake=1;
fi

#检查arena
arena_num=`$MYSQL_EXEC -e "select count(*) from t_arena_lucky" | egrep -o '[0-9]{0,10}'`;
if [ ! $arena_num -eq 1 ]; then
	    echo_error "$GROUP init arena_lucky data has error!";
		has_mistake=1;
else
	arena_date=`$MYSQL_EXEC -e "select begin_date from t_arena_lucky" | egrep -o '[0-9]{0,10}'`;
	cur_day=`date '+%Y%m%d'`
	#echo $arena_date - $arena_lucky_date
	arena_date=`expr $arena_date - $cur_day`;
	#echo $arena_date
	if [ ! $arena_date -eq 0 ] && [ ! $arena_date -eq 1 ]; then
			echo_error "$GROUP init arena_lucky data has error!";
			has_mistake=1;
	fi
fi

npc_num=5000
arena_num=`$MYSQL_EXEC -N -e "select count(*) from t_arena"`
if [ $arena_num -le 0 ]; then
	echo_error "$GROUP init arena data has error! arena is empty"
	has_mistake=1;
fi

user_num=`$MYSQL_EXEC -N -e "select count(*) from t_user where uid >= 20000 and level >= 14"`
if [ $arena_num -ne $((user_num + npc_num)) ]; then
	echo_error "$GROUP init arena data has error! arena num not equal user num"
	has_mistake=1;
fi

pass_exist=`$MYSQL_EXEC -N -e "show tables like 't_pass'"`
if [ "$pass_exist" ];then
	arena_his_num=`$MYSQL_EXEC -N -e "select count(*) from t_arena_history"`
	if [ $arena_his_num -lt $npc_num ];then
		echo_error "$GROUP init arena history data has error! arena history not enough"
		has_mistake=1;
	fi
fi

#检查活动配置是否刷过来了
old_activity_num=`$MYSQL_EXEC_FIRST_GROUP -e "select count(distinct name) from t_activity_conf" | egrep -o '[0-9]{0,10}' `
new_activity_num=`$MYSQL_EXEC -e "select count(distinct name) from t_activity_conf" | egrep -o '[0-9]{0,10}' `
if [ $old_activity_num -gt $new_activity_num  ]; then
	echo_error "$GROUP activity conf has error!";
	has_mistake=1;
fi


#检查game server config文件是否存在
if [ `checkLogicGsc $GROUP $LOGICIP` -ne 1 ]; then
	echo_error "$GROUP GSC conf not found in logic $LOGICIP";
	has_mistake=1;
else
	#检查game server config的合服时间配置是否正确
	MERGEDATE=`date +%Y%m%d100000`
	CONFMERGEDATE=`ssh $LOGICIP "grep MERGE_SERVER_OPEN_DATE $RPCFWPATH/conf/gsc/$SERVERPRE$GROUP/Game.cfg.php | egrep -o '[0-9]{8,}'"`

	if [ ! "$MERGEDATE" = "$CONFMERGEDATE" ]; then
		echo_error "$GROUP merge date has error, config is $CONFMERGEDATE, maybe $MERGEDATE!";
		has_mistake=1
	fi
fi

#检查id是否正常
echo_info "check id start ...!"
sh $BASEPATH/optool/CheckId.sh $GROUP $BASEPATH/data/dataproxy/id.xml $BASEPATH/data/dataproxy/data.xml
shell_result=$?
if [ $shell_result -eq 1 ]; then
		echo_error "check id $GROUP failed!"
		has_mistake=1
fi	
echo_info "check id end!"

if [ $has_mistake -eq 0 ]; then
	echo_info "$GROUP NO ERROR!SUCCESS!";
else
	echo_error "$GROUP HAS ERROR!FIXED IT!";
	exit 1;
fi