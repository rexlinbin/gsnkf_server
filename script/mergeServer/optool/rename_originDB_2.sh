#!/usr/bin/env bash

#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/formatOutput.sh
source $BASEPATH/optool/dbLcserverInfo.sh

function usage() {
	echo "Usage:";
	echo "		rename db pirate\$group to bak\$group";
	echo "		sh $0 groupid";
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

MAIN=`getLcserverIp $GROUP`

if [ -z $MAIN ]; then
	echo_error "can not get $GROUP main server!";
	exit;
fi

if [ `checkLcserverConfExist $GROUP` -eq 0 ]; then
    SIMPLEDBNAME=`getSimpleDBName $GROUP`;
	MASTERDB=`getMasterDBIP $GROUP`
	if [ -z $MASTERDB ]; then
		echo_error "$GROUP DB INFO not exist!";
		exit 0;
	fi
	if [ `ls -l $MYSQLPATH/bin/mysql_rename_db_2 | wc -l` -eq 0 ]; then
		echo_error "$MYSQLPATH/bin/mysql_rename_db_2 is not exist!";
		exit 1;
	fi
	echo_info "lcserver args not exist! rename_db $MASTERDB pirate$GROUP! please confirm [y]:";
	read p;
	if [ "$p" = 'y' ]; then
		PASSWD="";
		if [ ! -z "$MYSQLROOTPASSWD" ]; then
			PASSWD="-p$MYSQLROOTPASSWD";
		fi
		cd $MYSQLPATH/bin && sh mysql_rename_db_2 -o $MASTERDB -uroot $PASSWD -f pirate$SIMPLEDBNAME -t bak$SIMPLEDBNAME
	fi
	sleep 1;

	if [ `$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD -h$MASTERDB -N -e 'show databases;' | grep pirate$SIMPLEDBNAME | wc -l` -eq 0 ];then
		echo_info "rename DB pirate$GROUP done!";
		exit 0;
	else
		echo_error "some exception!CHECK IT!";
		exit 1;
	fi
else
	echo_error "lcserver args exist! don't rename db!";
	exit 1;
fi
