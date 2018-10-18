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
	echo_usage "	add lcserver args and rpcfw gsc"
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
	exit 1;
fi

GROUP=`getFirstGroupId $NEWGROUP`

MAIN=`getLcserverIp $GROUP`
if [ -z $MAIN ]; then
    echo_error "lcserver $SERVERPRE$NEWGROUP zookeeper node not exist!";
    exit 1;
fi
if [ `checkLcserverConfExist $GROUP ` -ne 0 ]; then
	echo_error "$SERVERPRE$NEWGROUP.args exist in server $MAIN";
	exit 1;
fi

lcserver_args_exits=`ssh $MAIN "if [ -f $MERGEOPBAK/lcserver/$SERVERPRE$GROUP.args ]; then echo 1; else echo 0; fi"`
if [ -z $lcserver_args_exits ] || [ $lcserver_args_exits -ne 1 ];then
	echo_error "$SERVERPRE$GROUP merge bak lcserver args not exist!";
	exit 1;
fi

new_lcserver_args_exists=`ssh $MAIN "if [ -f $LCSERVERPATH/conf/$SERVERPRE$NEWGROUP.args ]; then echo 1; else echo 0; fi"`
if [ -z $new_lcserver_args_exists ]; then
    echo_error "$MAIN ssh failed!";
    exit 1;
fi

SIMPLEDBNAME=`getSimpleDBName $NEWGROUP`
if [ $new_lcserver_args_exists -ne 1 ];then
	ssh $MAIN "cp $MERGEOPBAK/lcserver/$SERVERPRE$GROUP.args $LCSERVERPATH/conf/$SERVERPRE$NEWGROUP.args"
	ssh $MAIN "sed -i "s/$DBPRE$GROUP/$DBPRE$SIMPLEDBNAME/g" $LCSERVERPATH/conf/$SERVERPRE$NEWGROUP.args"
fi

echo_info "$SERVERPRE$NEWGROUP.args add in server $MAIN!done!";
