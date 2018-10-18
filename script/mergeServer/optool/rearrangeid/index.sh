#!/bin/bash

#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/dbLcserverInfo.sh
source $BASEPATH/optool/formatOutput.sh

ARGV=($(getopt -o t:d: -- "$@"))

###
#
# usage
#
###
function usage()
{
	echo "Usage:"
	echo "	Merge Server Steps:"
	echo "		IMPORT!before use it, please modify config.sh"
	echo "		1.	shutdown lcserver"
	echo "		2.	set id mark"
	echo "		3.	remove lcserver args and rpcfw conf gsc dir, use script:remove_LCargs_RPCgsc.sh"
	echo "		4.	merge data"  
	echo "		5.	add new group lcserver args use script:add_LC_args_gsc.sh"
	echo "		6.	add new group rpcfw conf gsc, use script:modify_game.sh"
	echo "		7.	generate ids, use script:GenID.sh"
	echo "		8.	init server, use script:init_server.sh"
	echo "		9.	modify boss info, use script:bossModifyLevel.sh"
	echo "		10.	fix timer, use script:fix_game_timer.sh"
	echo "		11.	check modify info, use script:check_merge_data.sh"
	echo "		12.	export tmp user info, use script:exportTmpUser.sh";
	echo "		13.	rename db, use script:rename_originDB.sh";
	echo "		14.	delete lcserver zookeeper node, use script:delete_LC_zookeeper.sh"
	echo "		15.	start server"
	echo 
	echo
	echo "	Merge Server Args:"
	echo "		-t target group id, e.g. 000_6_7_8_9"
	echo "		-d master db ip, e.g. 192.168.1.1"
	exit;
}

###
#
#	check environment:
#			include:
#				1.	php phpextension:pctnl mysqli amf zookeeper
#				2.	mysql
#				3.	dataproxy.xml
#				4.	dump dataproxy config file
#
###
function checkEnv()
{
	local env_error=0;
	local php_exist=0;
	#1.check php exist
	if [ ! -f $PHPPATH/bin/php ]; then
		echo_error "$PHPPATH/bin/php is not exist!please intall it!exit!"
		env_error=1;
	else
		php_exist=1;
	fi 
	
	#2.check php extension pctnl mysqli amf zookeeper
	if [ $php_exist -eq 1 ]; then
		if [ `$PHPPATH/bin/php -m | grep pcntl | wc -l` -ne 1 ]; then
			echo_error "$PHPPATH/bin/php have not extension pctnl!please intall it!exit!";
			env_error=1;
		fi
		if [ `$PHPPATH/bin/php -m | grep mysqli | wc -l` -ne 1 ]; then
			echo_error "$PHPPATH/bin/php have not extension mysqli!please intall it!exit!";
			env_error=1;
		fi
		if [ `$PHPPATH/bin/php -m | grep amf | wc -l` -ne 1 ]; then
			echo_error "$PHPPATH/bin/php have not extension amf!please intall it!exit!";
			env_error=1;
		fi
		if [ `$PHPPATH/bin/php -m | grep zookeeper | wc -l` -ne 1 ]; then
			echo_error "$PHPPATH/bin/php have not extension zookeeper!please intall it!exit!";
			env_error=1;
		fi
	else 
		echo_error "$PHPPATH/bin/php is not exist! so extension check do not run!";
	fi
	
	#3.check mysql exist
	if [ ! -f $MYSQLPATH/bin/mysql ]; then
		echo_error "$MYSQLPATH/bin/mysql is not exist!please intall it!exit!";
		env_error=1;
	fi
	if [ ! -f $MYSQLPATH/bin/mysqldump ]; then
		echo_error "$MYSQLPATH/bin/mysqldump is not exist!please intall it!exit!";
		env_error=1;
	fi
	
	#4.dump zookeeper data config xml to file
    if [ $php_exist -eq 1 ]; then
        $PHPPATH/bin/php $BASEPATH/script/ZKManager.php -o 'dump'	-p $ZKDATAPATH -z $ZKHOSTS -d $BASEPATH/data/dataproxy
        dump_ret=$?
        if [ $dump_ret -eq 1 ] || [ ! -f $BASEPATH/data/dataproxy/data.xml ]; then
            echo_error "$BASEPATH/data/dataproxy/data.xml is not exist!please check zookeeper config!exit!";
            env_error=1;
        fi
    else
        echo_error "$PHPPATH/bin/php is not exist! so dump zookeeper data config xml do not run!";
    fi
	
	if [ $env_error -eq 0 ]; then
		echo_info "No Errors in configure!you can execute merge!";
	else
		exit 1;
	fi
	
}

function mergeRun()
{
	local target=$1
	local mdbip=$2

    #because max length of mysql database name is 64, so need change long database name to simple format
    local dbtarget=`getSimpleDBName $target`
	if [ -z $target ] || [ -z $mdbip ]; then
		echo_error "merge server need two args:targetgroupid, mdbip!"
		exit;
	fi
	
	if [ `checkmysqlrun $mdbip` -ne 1 ]; then
		echo_error "$mdbip is not a database or mysqld is not running!"
		exit;
	fi
	
	if [ `checkdbisMaster $mdbip` -ne 1 ]; then
		echo_error "$mdbip is not a master database!"
		exit;
	fi 

	if [ -z `getLcserverIp $target` ]; then
		echo_error "$target group not found"
		exit;
	fi
	
	#check mysql user and password
	if [ `$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD -h$mdbip -e 'show databases' 2>/dev/null | wc -l` -eq 0 ]; then
		echo_error "$MYSQLUSER or MYSQLPASSWD has error!please check it";
		exit;
	fi
	
	# 1.shutdown lcserver
	echo_info "Start shut down Lcserver ...";
	
	echo_info "shut down lcserver $target ...";
	shutdownlcserver $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
		echo_error "shut down lserver $target failed!"
		exit;
	fi
	echo_info "shut down lcserver $target completed";
	
	# 2. remove lcserver and rpcfw conf args
	echo_info "remove lcserver $target args ...";
	sh $BASEPATH/optool/remove_LCargs_RPCgsc.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
		echo_error "remove lcserver args $target failed!"
		exit;
	fi
	echo_info "remove lcserver $target args down";


	# 3. rearrange id
	echo_info "rearrange id for $target start...";
	sh $BASEPATH/optool/rearrangeid/rearrange_id.sh -g $target  \
									-u $MYSQLUSER -p $MYSQLPASSWD -x $BASEPATH/data/dataproxy/data.xml
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo "rearrange id failed!"
			exit;
	fi
	echo_info "rearrange id ok!";

	# 4. rollback lcserver and rpcfw conf args
	echo_info "rollback lcserver and rpcfw conf ..."
	sh $BASEPATH/optool/rearrangeid/rollback_LCargs_RPCgsc.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "add lcserver args server $target failed!"
			exit;
	fi
	echo_info "rollback lcserver and rpcfw conf end!";
	
	# 5. set ids
	echo_info "set ids start ..."
	sh $BASEPATH/optool/rearrangeid/set_id.sh -g $target -u $MYSQLUSER -p $MYSQLPASSWD
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "set ids $target failed!"
			exit;
	fi	
	echo_info "set ids end!"
	
	
	echo_info "done please start server"
}

target=""
mdbip=""	
#获取参数
for((i = 0; i < ${#ARGV[@]}; i++)) {
	eval opt=${ARGV[$i]}
	case $opt in
		-t)
			((i++));
			eval target=${ARGV[$i]};
			;;
		-d)
			((i++));
			eval mdbip=${ARGV[$i]};
			;;
		--)
			break
			;;
	esac
}

#check target is set
if [ -z $target ]; then
	echo "-t should be set!";
	usage;
fi

#check mdbip is set
if [ -z $mdbip ]; then
	echo "-d should be set!";
	usage;
fi

checkEnv
mergeRun $target $mdbip