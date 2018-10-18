#!/usr/bin/env bash

#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/formatOutput.sh
source $BASEPATH/optool/dbLcserverInfo.sh

#usage print function
function usage() {
	echo_usage "Usage:"
	echo_usage "	init server"
	echo_usage "	sh $0 groupid"
}

#check args
if [ $# -ne 1 ]; then
	usage;
	exit 1;
fi

NEWGROUP=$1

#valid group id
if [ `validGroupId $NEWGROUP` -ne 1 ]; then
	echo_error "$NEWGROUP is invalid";
	usage;
	exit;
fi

GROUP=`getFirstGroupId $NEWGROUP`

MAIN=`getLcserverIp $GROUP`
if [ -z $MAIN ]; then
	echo_error "lcserver $SERVERPRE$NEWGROUP zookeeper node not exist!";
	exit 1;
fi

if [ `checkLcserverConfExist $GROUP` -ne 0 ]; then
	echo_error "$SERVERPRE$GROUP.args exist in server $MAIN";
	exit 1;
fi

if [ `checkMainGsc $GROUP` -ne 0 ]; then
	echo_error "group $GROUP rpcfw gsc exist in server $MAIN";
	exit 1;
fi

if [ `ssh $MAIN "ls -l $LCSERVERPATH/conf/$SERVERPRE$NEWGROUP.args" | wc -l` -ne 1 ]; then
	echo_error "$SERVERPRE$NEWGROUP.args not exist in server $MAIN";
	exit 1;
fi

if [ `ssh $MAIN "ls -d $RPCFWPATH/conf/gsc/$SERVERPRE$NEWGROUP" | wc -l` -ne 1 ]; then
	echo_error "group $NEWGROUP rpcfw gsc not exist in server $MAIN";
	exit 1;
fi

mkdir -p $BASEPATH/tmp/done
if [ -f $BASEPATH/tmp/done/init_$NEWGROUP ]; then
	echo_notice "$SERVERPRE$NEWGROUP has already init!";
	exit 0;
fi

scp $BASEPATH/script/init_merge.sh $MAIN:$RPCFWPATH/script/
if [ $? -eq 255 ]; then
    echo_error "ssh $MAIN failed!";
    exit 1;
fi
ssh $MAIN "cd $RPCFWPATH/script && sh init_merge.sh $SERVERPRE$NEWGROUP"

ssh_restart=$?
if [ $ssh_restart -eq 255 ]; then
	echo_error "$SERVERPRE$NEWGROUP init cancel!";
	exit 1;
fi

cur_time=`date +%s`
cur_day=`date '+%Y%m%d'`
arena_reward_time=`date -d "$cur_day 22:00:00" +%s`
if [ $cur_time -lt $arena_reward_time ];then
	arena_lucky_date=`date '+%Y%m%d'`
else
	arena_lucky_date=`date -d '+1 day' '+%Y%m%d'`
fi


ssh $MAIN "cd $RPCFWPATH/script && $BINPATH/btscript $SERVERPRE$NEWGROUP ModifyArenaDate.php $arena_lucky_date"


echo_info "$SERVERPRE$NEWGROUP init done on server $MAIN!";
touch $BASEPATH/tmp/done/init_$NEWGROUP
exit 0;