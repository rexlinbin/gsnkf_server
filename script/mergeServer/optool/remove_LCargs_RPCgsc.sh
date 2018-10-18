#!/usr/bin/env bash

#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/formatOutput.sh
source $BASEPATH/optool/dbLcserverInfo.sh

#usage function
function usage()
{
	echo_usage "Usage:"
	echo_usage "	remove lcserver args and rpcfw gsc with group id"
	echo_usage "	sh $0 groupid"
}

if [ $# -lt 1 ]; then
	usage;
	exit 1;
fi

GROUP=$1

#validate input group
if [ `validGroupId $GROUP` -ne 1 ]; then
	echo_error "$GROUP is invalid";
	usage;
	exit 1;
fi

#get lcserver ip by group id
MAIN=`getLcserverIp $GROUP`

#check the lcserver ip is null
if [ -z $MAIN ]; then
	echo_error "can not get $GROUP main server!";
	exit 1;
fi

#check lcserver is running
if [ `getLcserverProcssNum $GROUP` -eq 0 ]; then
	echo_info "REMOVE $GROUP lcserver args and rpcfw gsc in $MAIN";
	
	#check lcserver conf file exist
	if [ `checkLcserverConfExist $GROUP` -eq 0 ]; then
		echo_notice "$GROUP lcserver args has removed!";
	else
		ssh $MAIN "mkdir -p $MERGEOPBAK/lcserver";
		ssh $MAIN "mv $LCSERVERPATH/conf/$SERVERPRE$GROUP.args $MERGEOPBAK/lcserver/$SERVERPRE$GROUP.args"
	fi

	#check rpcfw conf file exist
	if [ `checkMainGsc $GROUP` -eq 0 ]; then 
		echo_notice "$GROUP rpcfw conf has removed!";
	else
		ssh $MAIN "mkdir -p $MERGEOPBAK/rpcfw"
		ssh $MAIN "mv $RPCFWPATH/conf/gsc/$SERVERPRE$GROUP $MERGEOPBAK/rpcfw/$SERVERPRE$GROUP"
	fi
	
	if [ `checkLcserverConfExist $GROUP` -eq 0 ]; then
		echo_info "remove lcserver args and rpcfw gsc which group:$GROUP done!";
		exit 0;
	fi
else
	echo_error "Lcserver $GROUP process exist!";
	exit 1;
fi

exit 1;