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
	echo_usage "	fixed boss data on target group";
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
GROUP=`getFirstGroupId $NEWGROUP`
GROUP_DB_NAME=`getSimpleDBName $GROUP`
GROUPDB=`getMasterDBIP $GROUP`
NEWGROUPDB=`getMasterDBIP $NEWGROUP`

NEWGROUP=`getSimpleDBName $NEWGROUP`
#check modify data has executed
boss_level_more_1_count=`$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD -h$NEWGROUPDB pirate$NEWGROUP -e 'select boss_id from t_boss where level > 1;' | grep -v boss_id | wc -l`
if [ $boss_level_more_1_count -ne 0 ]; then
	echo_notice "may be this shell has executed!"
	exit 0;
fi

#call php modify data
$PHPPATH/bin/php $BASEPATH/script/BossModifyLevel.class.php -u$MYSQLUSER \
	-p$MYSQLPASSWD -s$GROUPDB -t$NEWGROUPDB -b$GROUP_DB_NAME -d$NEWGROUP

shell_result=$?
if [ -z $shell_result ] || [ $shell_result -eq 1 ]; then
		echo_error "boss modify lcserver $NEWGROUP failed!"
		exit 1;
fi
exit 0