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
	echo_usage "	check id on slave db with group id"
	echo_usage "	sh $0 groupid id.xml data.xml"
}

if [ $# -lt 3 ]; then
	usage;
	exit 1;
fi

GROUP=$1
ID_XML_PATH=$2
DATA_XML_PATH=$3

# valid input group id
if [ `validGroupId $GROUP` -ne 1 ]; then
	echo_error "$GROUP is invalid";
	usage;
	exit 1;
fi

# get slave database ip
SIMPLEDBNAME=`getSimpleDBName $GROUP`
SLAVEDB=`getDataproxyIP $GROUP`
MASTERDB=`getMasterDBIP $GROUP`
if [ -z $SLAVEDB ]; then
	echo_error "$GROUP slave db not exist!exit!";
	exit 1;
fi

# check id between db and dataproxy
$PHPPATH/bin/php $BASEPATH/script/CheckId.php $MASTERDB $SLAVEDB pirate$SIMPLEDBNAME $MYSQLUSER $MYSQLPASSWD $ID_XML_PATH $DATA_XML_PATH
msg=$?
if [ $msg -ne 0 ]; then
	echo "CHECK ID FAILED!"
	exit 1;
fi

exit 0;
