#!/bin/bash

if [ -z $BASEPATH ]; then
	echo "BASEPATH is not exist!;exit!";
	exit;
fi

if [ -z $ZKHOSTS ]; then
	echo "ZKHOSTS is not exist! please source $BASEPATH/shell/config.sh"
	exit;
fi

source "$BASEPATH/optool/formatOutput.sh"

MAX_LENGTH_MYSQL_DATABASE_NAME=64

###
#
# get simple db name
# (because max length of mysql database name is 64, so need change long database name to simple format
#
###
function getSimpleDBName
{
    local GROUP=$1

    if [ -z "$GROUP" ]; then
        echo_error "getSimpleDBName need one args:group!";
        exit 1;
    fi

    local db_prefix_length=${#DBPRE}
    local max_length=$(($MAX_LENGTH_MYSQL_DATABASE_NAME - $db_prefix_length))
    if [ ${#GROUP} -gt $max_length ]; then

        local BASEGROUP=`echo $GROUP | cut -d '_' -f 1`
        local FGROUP=
        local __GROUP=

        for i in `echo $GROUP | sed "s/_/ /g"`;
        do
            if [ "$i" == "$BASEGROUP" ]; then
                GROUP=$BASEGROUP;
            elif [ $(($i - 1)) -ne $__GROUP ] || [ -z "$FGROUP" ]; then
                if [ ! -z "$FGROUP" ]; then
                    if [ $(($__GROUP - 1)) -eq $FGROUP ]; then
                        GROUP=${GROUP}_${FGROUP}_${__GROUP}
                    else
                        if [ $__GROUP -ne $FGROUP ]; then
                            GROUP=${GROUP}_${FGROUP}__${__GROUP}
                        else
                            GROUP=${GROUP}_${FGROUP}
                        fi
                    fi
                fi
                FGROUP=$i
            fi
        __GROUP=$i
        done

        if [ $__GROUP -eq $FGROUP ]; then
            GROUP=${GROUP}_${FGROUP}
        elif [ $(($__GROUP -1)) -eq $FGROUP ]; then
            GROUP=${GROUP}_${FGROUP}_${__GROUP}
        else
            GROUP=${GROUP}_${FGROUP}__${__GROUP}
        fi
    fi
    echo $GROUP
}

###
#
# get dataproxy ip
#
###
function getDataproxyIP()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "getDataproxyIP need one args:groupid";
		exit;
	fi

    GROUP=`getSimpleDBName $GROUP`
	$PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'getDataproxyIP'	-p $ZKDATAPATH$ZKDATAPREFIX -s $GROUP -z $ZKHOSTS
}


###
#
# get mysql slave database ip
#
###
function getSlaveDBIP()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get slavedb need one args:groupid";
		exit;
	fi

    GROUP=`getSimpleDBName $GROUP`
	$PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'getSDBIP'	-p $ZKDATAPATH$ZKDATAPREFIX -s $GROUP -z $ZKHOSTS
}

###
#
# get mysql master database ip
#
###
function getMasterDBIP()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get master db need one args:groupid";
		exit;
	fi
	local SLAVEDB=`getSlaveDBIP $GROUP`
	if [ -z $SLAVEDB ]; then
		exit;
	fi
	if [ -z $MYSQLPATH ]; then
		echo_error "MYSQLPATH is not exist! please source $BASEPATH/shell/config.sh"
		exit;
	fi
	if [ `ssh $SLAVEDB "ls -l $MYSQLPATH/var/master.info" | wc -l` -ne 1 ]; then
		echo_error "SLAVE DB master.info file is not exist!";
		exit;
	fi
	ssh $SLAVEDB "grep -Eo '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}' $MYSQLPATH/var/master.info"
}

###
#
# get lcserver ip
#
###
function getLcserverIp()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get lcserver ip need one args:groupid";
		exit;
	fi
	$PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'getMainIP'	-p $ZKLCSERVERPATH$ZKLCSERVERPREFIX   -s $GROUP -z $ZKHOSTS
}

###
#
# get lcserver refer group id
#
###
function getLcserverReferGroupId()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get lcserver refer group id need one args:groupid";
		exit;
	fi
	local REFER=`$PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'getMainRefer'	-p $ZKLCSERVERPATH$ZKLCSERVERPREFIX  -s $GROUP -z $ZKHOSTS`
	if [ -z $REFER ]; then
		echo $GROUP;
	else
		echo $REFER;
	fi
}

###
#
# check lcserver args exist
#
###
function checkLcserverConfExist()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get slavedb need one args:groupid";
		exit;
	fi
	local LCSERVERIP=`getLcserverIp $GROUP`
	ssh $LCSERVERIP "ls -l $LCSERVERPATH/conf | grep $SERVERPRE$GROUP.args | grep -v grep" | wc -l;
}

###
#
# check lcserver args exist
#
###
function checkMainGsc()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get slavedb need one args:groupid";
		exit;
	fi
	local LCSERVERIP=`getLcserverIp $GROUP`
	ssh $LCSERVERIP "ls -l $RPCFWPATH/conf/gsc | grep '$SERVERPRE$GROUP\$' | grep -v grep" | wc -l;
}

###
#
# get lcserver process number
#
###
function getLcserverProcssNum()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get lcserver process num need one args:groupid";
		exit;
	fi
	local LCSERVERIP=`getLcserverIp $GROUP`
	ssh $LCSERVERIP "ps -ef | grep lcserver | grep '\-G\s\s*$SERVERPRE$GROUP\s\s*\-w' | grep -v grep" | wc -l
}

###
#
# valid group id
#
###
function validGroupId()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "valid group id need one args:groupid";
		echo 0;
	fi
	if [ `echo $GROUP | grep -Eo "[0-9_]{3,}"` == $GROUP ]; then
		echo 1;
	else
		echo 0;
	fi 
}

###
#
# get first group id
#
###
function getFirstGroupId()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get first group id need one args:groupid";
		exit;
	fi
	local baseGameId=`echo $GROUP|cut -d_ -f1`
	local firstGameId=`echo $GROUP|cut -d_ -f2`
	GROUP=`expr $baseGameId + $firstGameId`
	if [ "$baseGameId" == "000" ];then
		GROUP=`printf "%03d" $GROUP`
	else
		GROUP=$GROUP
	fi
	getLcserverReferGroupId $GROUP
}

###
#
# get group list
#
###
function getGroupList()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get group list need one args:groupid";
		exit;
	fi
	local GROUPLIST=(${GROUP//_/ })
	local GROUPNUM=${#GROUPLIST[@]}
	local LIST=""
	local LASTGROUP=""
	if [ $GROUPNUM -gt 1 ]; then
		GROUPBASE=${GROUPLIST[0]}
		for i in ${GROUPLIST[@]}
		do
			if [ $i != $GROUPBASE ]; then
				LASTGROUP=`expr $GROUPBASE + $i`
				LASTGROUP=`printf "%03d" $LASTGROUP`
				LIST=$LIST" "$LASTGROUP
			fi
		done
	fi
	echo $LIST
}

###
#
# get merge group list
#
###
function getMergeGroupList()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "get merge group list need one args:groupid";
		exit;
	fi
	local GROUPLIST=`getGroupList $GROUP`
	declare -A __flags
	local LIST=""
	for i in $GROUPLIST
	do
		local k=`getLcserverReferGroupId $i`
		if [ -z ${__flags[$k]} ] || [ ${__flags[$k]} != 1 ]; then
			LIST=$LIST" "$k
			__flags[$k]=1;
			for m in `getGroupList $k`;
			do
				if [ `echo $GROUPLIST | grep $m | wc -l` -ne 1 ]; then
					echo_error "getMergeGroupList:groupid error!";
					exit;
				fi 
			done
		fi
	done
	echo $LIST
}

###
#
# get one logic ip by game group id
#
###
function getLogicGroup()
{
	local MAINIP=$1
	local GROUP=$2
	if [ -z $GROUP ] || [ -z $MAINIP ]; then
		echo_error "get logic group need two args:mainip, groupid";
		exit;
	fi

	local LOGICGROUP=`ssh $MAINIP "grep -Eo '\-i [/a-zA-Z0-9_]*' $LCSERVERPATH/conf/$SERVERPRE$GROUP.args| grep -Eo '$ZKPREFIX/logic/[a-zA-Z0-9_]*'"`
    if [ $? -eq 255 ]; then
        echo_error "ssh $MAINIP error!";
        exit;
    fi

	if [ -z $LOGICGROUP ] || [ $LOGICGROUP == $ZKLOGICPATH ]; then
		echo "";
	else
		echo $LOGICGROUP grep -Eo "[a-ZA-Z0-9]*$" -a
	fi
}

###
#
# get logic list by game group id
#
###
function getLogicIpByGroupId()
{
	local MAINIP=$1
	local GROUP=$2
	if [ -z $GROUP ] || [ -z $MAINIP ]; then
		echo_error "get all logic ip by group id need two args:mainip, groupid";
		exit;
	fi

	local LOGICGROUP=`ssh $MAINIP "grep -Eo '\-i [/a-zA-Z0-9_]*' $LCSERVERPATH/conf/$SERVERPRE$GROUP.args| grep -Eo '$ZKLOGICPATH/[a-zA-Z0-9_]*'"`
    if [ $? -eq 255 ]; then
        echo_error "ssh $MAINIP error!";
        exit;
    fi

	if [ -z $LOGICGROUP ]; then
		LOGICGROUP=$ZKLOGICPATH;
	fi
	$PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'getLogicIP'	-p $LOGICGROUP -z $ZKHOSTS
}

###
#
# get one logic ip by game group id
#
###
function getOneLogicIPByGroupId()
{
	getLogicIpByGroupId $1 $2 | head -n 1
}


###
#
# check logic gsc dir exist
#
###
function checkLogicGsc()
{
	local GROUP=$1
	local LOGICIP=$2
	if [ -z $GROUP ] || [ -z $LOGICIP ]; then
		echo_error "get one logic ip by group id need two args:groupid logicip";
		exit;
	fi
	gscExist=`ssh $LOGICIP "ls -l $RPCFWPATH/conf/gsc | grep '$SERVERPRE$GROUP\$'" | wc -l`
	if [ $gscExist -ne 1 ]; then
		echo 0;
	else
		echo 1;
	fi
}


###
#
# check mysql run
#
###
function checkmysqlrun()
{
	local dbip=$1
	if [ -z $dbip ]; then
		echo_error "check mysql is running need one args:dbip";
		exit;
	fi
	if [ `ssh $dbip "ps -ef | grep mysqld_safe | grep -v grep" | wc -l` -ne 1 ]; then
		echo 0;
	else
		echo 1;
	fi
}

###
#
# check ip is a master db
# check with the machine if run dataproxy, it is not safe
#
###
function checkdbisMaster()
{
	local dbip=$1
	if [ -z $dbip ]; then
		echo_error "check mysql is master need one args:dbip";
		exit;
	fi
	if [ `ssh $dbip "ps -ef | grep dataproxy | grep supervisor | grep -v grep" | wc -l` -eq 1 ]; then
		echo 0;
	else
		echo 1;
	fi
}

###
#
# check ip is a master db
# check with the machine if run dataproxy, it is not safe
#
###
function shutdownlcserver()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "shut down lcserver need one args:groupid";
		exit 1;
	fi
	local lcserverip=`getLcserverIp $GROUP`;
	if [ -z $lcserverip ]; then
		echo_error "get lcserver ip with group id $GROUP failed!";
		exit 1;
	fi
	ssh $lcserverip "ps -ef|grep -E '\-G\s\s*$SERVERPRE$GROUP\s\s*'|grep lcserver|grep -v grep | awk '{print $2}'" > /tmp/.$GROUP.lcserver.tmp
    if [ $? -eq 255 ]; then
        echo_error "ssh $lcserverip failed!";
        exit 1;
    fi
	count=`cat /tmp/.$GROUP.lcserver.tmp | wc -l`
	if [ $count -eq 0 ]; then
		cat /tmp/.$GROUP.lcserver.tmp
		echo_info "close lcserver $GROUP failed: already closed"
		return;
	fi
	
	cat /tmp/.$GROUP.lcserver.tmp
	echo -n "kill these process? [y/n]"
	read p
	if [ "$p" = "n" ]; then
		exit 1;
	fi
	
	local supervisor_pid=`cat /tmp/.$GROUP.lcserver.tmp | grep supervisor | awk '{print $2}'`
	if [ "$supervisor_pid" != "" ]; then
		ssh $lcserverip "kill -9 $supervisor_pid"
	fi
	local pid=`cat /tmp/.$GROUP.lcserver.tmp | grep -v supervisor | awk '{print $2}'`
	if [ "$pid" != "" ]; then
		ssh $lcserverip "kill -9 $pid"
	fi
	echo_info "close lcserver $GROUP ok"
}

###
#
# check salve db status is running
#
###
function checkslavedbstatus()
{
	local GROUP=$1
	if [ -z $GROUP ]; then
		echo_error "check salve db  need one args:groupid";
		exit 1;
	fi
	
	local sdb=`getSlaveDBIP $GROUP`
    if [ -z $sdb ]; then
        echo_error "get slave db ip for group $i failed!"
        return 0;
    fi
	local mysql_slave_status=`$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD -h$sdb -e 'show slave status\G' | grep -i 'Slave_SQL_Running' | grep -i 'yes' | wc -l`
	
	return $mysql_slave_status
}