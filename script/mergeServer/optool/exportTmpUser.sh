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
	echo_usage "	export tmp user"
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

SIMPLEDBNAME=`getSimpleDBName $GROUP`
SLAVEDB=`getSlaveDBIP $GROUP`

if [ -z $SLAVEDB ]; then
	echo_error "$GROUP slave db not exist!exit!";
	usage;
	exit 1;
fi

mkdir -p $MERGEPATH/$GROUP/exportSQL
$MYSQLPATH/bin/mysqldump -u$MYSQLUSER -p$MYSQLPASSWD \
	-h $SLAVEDB pirate$SIMPLEDBNAME t_tmp_user > \
	$MERGEPATH/$GROUP/exportSQL/$GROUP.t_tmp_user.sql