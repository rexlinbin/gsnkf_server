#!/bin/bash

#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/formatOutput.sh
source $BASEPATH/optool/dbLcserverInfo.sh

NEWGROUP=$1

function usage()
{
	echo_usage "Usage:";
	echo_usage "	fixed game timer on target group";
	echo_usage "	sh $0 targetGroupId";
}

if [ $# -ne 1 ];then
	usage;
	exit 1;
fi

#valid input group
if [ `validGroupId $NEWGROUP` -ne 1 ]; then
	echo_error "$NEWGROUP is invalid";
	usage;
	exit 1;
fi

#get first group
FIRST_GROUP=`getFirstGroupId $NEWGROUP`
FIRST_GROUP_DB_NAME=`getSimpleDBName $FIRST_GROUP`
FIRST_GROUP_DB_IP=`getMasterDBIP $FIRST_GROUP`
NEWGROUP_DB_IP=`getMasterDBIP $NEWGROUP`

NEWGROUP_DB_NAME=`getSimpleDBName $NEWGROUP`



#call php modify data
$PHPPATH/bin/php $BASEPATH/script/FixGameTimer.php -u$MYSQLUSER -p$MYSQLPASSWD \
	-s$FIRST_GROUP_DB_IP -t$NEWGROUP_DB_IP -b$FIRST_GROUP_DB_NAME -d$NEWGROUP_DB_NAME

shell_result=$?
if [ $shell_result -ne 0 ]; then
		echo_error "fix $NEWGROUP timer failed!"
		exit 1;
fi
exit 0