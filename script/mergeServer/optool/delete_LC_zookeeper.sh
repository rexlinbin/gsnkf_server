#!/usr/bin/env bash

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
	echo_usage "	delete lcserver zookeeper node"
	echo_usage "	sh $0 groupid"
}

if [ $# -lt 1 ]; then
	usage;
	exit;
fi

GROUP=$1

if [ `validGroupId $GROUP` -ne 1 ]; then
	echo_error "$GROUP is invalid";
	usage;
	exit;
fi

GROUP=`getLcserverReferGroupId $GROUP`

MAIN=`getLcserverIp $GROUP`

if [ -z $MAIN ]; then
    echo_error "$GROUP main host not is exist!";
    exit;
fi

if [ `checkLcserverConfExist $GROUP` -eq 0 ]; then
	echo_info "lcserver args not exist! call php delete lcserver zookeeper node!GROUP:$1";
	GROUPLIST=`getGroupList $GROUP`
	for i in $GROUPLIST
	do
		$PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'delete' \
			-p $ZKLCSERVERPATH$ZKLCSERVERPREFIX$i -z $ZKHOSTS
	done
	$PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'delete' \
		-p $ZKLCSERVERPATH$ZKLCSERVERPREFIX$GROUP -z $ZKHOSTS
else
	echo_error "lcserver args exist! don't delete this lcserver zookeeper node!GROUP:$1";
	exit;
fi

echo_info "delete zookeeper node $GROUP done!"