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
	echo_usage "	generate rpcfw gsc with group id"
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

FIRSTGROUP=`getFirstGroupId $GROUP`
LCSERVERIP=`getLcserverIp $FIRSTGROUP`
LOGICIP=`getOneLogicIPByGroupId $LCSERVERIP $GROUP`

if [ -z $LOGICIP ]; then
	echo_error "getLogicIp by group id $GROUP failed!"
	exit 1;
fi

#检查game server config文件是否存在
one_logic_not_exist=0;
for i in `getLogicIpByGroupId $LCSERVERIP $GROUP`;
do
	if [ `checkLogicGsc $GROUP $i` -eq 0 ]; then
		one_logic_not_exist=1;
		break;
	fi
done

if [ $one_logic_not_exist -eq 0 ]; then
	echo_notice "$GROUP GSC conf has found in all logics";
	exit 0;
fi

mkdir -p $MERGEPATH/$GROUP/gsc
GROUPLIST=`getMergeGroupList $GROUP`
for i in $GROUPLIST; 
do
	mkdir -p $MERGEPATH/$GROUP/gsc/$SERVERPRE$i
	if [ `checkLogicGsc $i $LOGICIP` -ne 1 ]; then
		echo_error "$i GSC conf has not found in logic $LOGICIP";
		exit 1;
	fi
	scp $LOGICIP:$RPCFWPATH/conf/gsc/$SERVERPRE$i/Game.cfg.php $MERGEPATH/$GROUP/gsc/$SERVERPRE$i/;
	sed -i "s/GameConf/GameConf$i/g" $MERGEPATH/$GROUP/gsc/$SERVERPRE$i/Game.cfg.php;
	sed -i "s/ArenaDateConf/ArenaDateConf$i/g" $MERGEPATH/$GROUP/gsc/$SERVERPRE$i/Game.cfg.php;
done
LIST=`echo $GROUPLIST | sed -e "s/ /;/g"`
mkdir -p $MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP
$PHPPATH/bin/php $BASEPATH/script/GenerateGameConf.php -p $MERGEPATH/$GROUP/gsc -s $LIST -t $MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP/Game.cfg.php
scp $LOGICIP:$RPCFWPATH/conf/gsc/$SERVERPRE$FIRSTGROUP/index.php $MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP/;

#验证PHP文件合法性
syntax_valid=`$PHPPATH/bin/php -l $MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP/Game.cfg.php`
if [ `echo $syntax_valid | grep -Eo "^No syntax errors" -a | wc -l` -ne 1 ]; then
	echo_error "$MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP/Game.cfg.php has syntax error!exit!";
	exit 1;
fi

for i in `getLogicIpByGroupId $LCSERVERIP $GROUP`;
do
	scp -r $MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP $i:$RPCFWPATH/conf/gsc/; 
done
scp -r $MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP $LCSERVERIP:$RPCFWPATH/conf/gsc/;

for i in `getLogicIpByGroupId $LCSERVERIP $GROUP`;
do
	if [ `checkLogicGsc $GROUP $i` -eq 0 ]; then
		echo_error "$GROUP GSC conf has not found in logic $i!may be scp failed!";
		exit 1;
	fi
done

echo_info "$GROUP GSC in dir $MERGEPATH/$GROUP/gsc/$SERVERPRE$GROUP!";
exit 0;
