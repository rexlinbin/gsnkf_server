#!/bin/bash

#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/dbLcserverInfo.sh
source $BASEPATH/optool/formatOutput.sh

ARGV=($(getopt -o t:d:m: -- "$@"))

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
	echo "		11.	deal special table, use script:deal_special_table.sh"
	echo "		12.	check modify info, use script:check_merge_data.sh"
	echo "		13.	export tmp user info, use script:exportTmpUser.sh";
	echo "		14.	rename db, use script:rename_originDB.sh";
	echo "		15.	delete lcserver zookeeper node, use script:delete_LC_zookeeper.sh"
	echo "		16.	start server"
	echo 
	echo
	echo "	Merge Server Args:"
	echo "		-t target group id, e.g. 000_6_7_8_9"
	echo "		-d master db ip, e.g. 192.168.1.1"
	echo "		-m multi process num; default 1"
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

function checkOtherProcess()
{
	echo_info "check other process"
	local target=$1
	local pid_file=$BASEPATH/tmp/${target}.pid
	if [ -e $pid_file ]; then
		last_pid=`cat $pid_file`
		last_pid_exist=`ps axu | awk '{print $2}'|grep $last_pid`
		if [ "$last_pid_exist" ]; then
			echo_error "pid:$last_pid is merging $target"
			exit;
		fi
		echo_info "lastpid:$last_pid not running"
	fi
	echo_info "cur process $$" 
	echo $$ > $pid_file
}


function afterMerge()
{
	echo_info "do something after merge"
	echo_notice "need update server info after start lcserver on cross host manually!!!!!!!!!!!";
	#script_file=$BASEPATH/optool/after_merge.sh
	#if [ -e $script_file ];then
	#	sh $script_file
	#else
	#	echo_error "not found $script_file"
	#	exit
	#fi
}



function mergeRun()
{
	local target=$1
	local mdbip=$2
	local multi_process=$3

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

	if [ ! -z `getLcserverIp $target` ]; then
		echo_error "$target group may be has merged!"
		exit;
	fi
	
	#check mysql user and password
	if [ `$MYSQLPATH/bin/mysql -u$MYSQLUSER -p$MYSQLPASSWD -h$mdbip -e 'show databases' 2>/dev/null | wc -l` -eq 0 ]; then
		echo_error "$MYSQLUSER or MYSQLPASSWD has error!please check it";
		exit;
	fi
	
	local GROUPLIST=`getMergeGroupList $target`
	if [ -z "$GROUPLIST" ]; then
		echo_error "getMergeGroupList $target failed! please check it";
		exit;
	fi
	local __GROUPLIST=`echo $GROUPLIST | sed -e "s/ /;/g"`
	
	#check slave db is running
	for i in $GROUPLIST;
	do
		echo_info "check slave db for group $i ...";
		checkslavedbstatus $i
		shell_result=$?
        if [ -z "$shell_result" ] || [ $shell_result -eq 0 ]; then
			echo_error "check slave db for $i failed!"
			exit;
		fi		
		echo_info "check slave db for group $i done";
	done
	
	#check game. if there is any activity stop the merge
	echo_info "check game state";
	sh $BASEPATH/optool/check_game.sh -i "$__GROUPLIST" -u $MYSQLUSER -p $MYSQLPASSWD -x $BASEPATH/data/dataproxy/data.xml
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
		echo_error "chech game failed!"
		exit;
	fi
	
	# 1.shutdown lcserver
	echo_info "Start shut down Lcserver ...";
	for i in $GROUPLIST;
	do
		echo_info "shut down lcserver $i ...";
		shutdownlcserver $i
		shell_result=$?
		if [ $shell_result -eq 1 ]; then
			echo_error "shut down lserver $i failed!"
			exit;
		fi
		echo_info "shut down lcserver $i completed";
	done
	
	# 2. set id mark
	echo_info "set id mark $__GROUPLIST start...";
	sh $BASEPATH/optool/set_id_mark.sh -i "$__GROUPLIST" -u $MYSQLUSER -p $MYSQLPASSWD 
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
		echo_error "set id mark $__GROUPLIST failed!"
		exit;
	fi
	echo_info "set id mark $__GROUPLIST ok!";
	
	# 3. remove lcserver and rpcfw conf args
	for i in $GROUPLIST;
	do
		echo_info "remove lcserver $i args ...";
		sh $BASEPATH/optool/remove_LCargs_RPCgsc.sh $i
		shell_result=$?
		if [ $shell_result -eq 1 ]; then
			echo_error "remove lcserver args $i failed!"
			exit;
		fi
		echo_info "remove lcserver $i args down";
	done

	# 4. merge data
	echo_info "merge server $__GROUPLIST start...";
	if [ ! -f $BASEPATH/tmp/done/$dbtarget ]; then
		sh $BASEPATH/shell/index.sh -i "$__GROUPLIST" -u $MYSQLUSER -p $MYSQLPASSWD -d $mdbip -t $dbtarget -x $BASEPATH/data/dataproxy/data.xml -m $multi_process
		shell_result=$?
		if [ $shell_result -eq 1 ]; then
			echo_error "merge server $__GROUPLIST failed!"
			exit;
		fi
	fi
	echo_info "merge server $__GROUPLIST ok!";
	
	# 5. add lcserver
	echo_info "start add lcserver arg..."
	sh $BASEPATH/optool/add_LC_args_gsc.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "add lcserver args server $target failed!"
			exit;
	fi
	echo_info "add lcserver arg end!";
	
	# 6. add rpcfw conf gsc
	echo_info "start add rpcfw conf ..."
	sh $BASEPATH/optool/modify_game.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "add rpcfw conf server $target failed!"
			exit;
	fi
	echo_info "add rpcfw conf end!"

	# 7. generate ids
	echo_info "generate ids start ..."
	sh $BASEPATH/optool/GenID.sh $target $BASEPATH/data/dataproxy/id.xml $BASEPATH/data/dataproxy/data.xml
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "generate ids $target failed!"
			exit;
	fi	
	echo_info "generate ids end!"
	
	# 8. init server
	echo_info "init server start ..."
	sh $BASEPATH/optool/init_server.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "init server $target failed!"
			exit;
	fi	
	echo_info "init server end!"

	# 9. modify boss info
	echo_info "modify boss info start ..."
	sh $BASEPATH/optool/bossModifyLevel.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "modify boss data server $target failed!"
			exit;
	fi	
	echo_info "modify boss info end!"
	
	# 10. fix timer
	echo_info "fix timer start ..."
	sh $BASEPATH/optool/fix_game_timer.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "fix timer server $target failed!"
			exit;
	fi	
	echo_info "fix timer end!"
	
	# 11. deal special table
	echo_info "deal special table start ..."
	sh $BASEPATH/optool/deal_special_table.sh -i "$__GROUPLIST" -u $MYSQLUSER -p $MYSQLPASSWD -x $BASEPATH/data/dataproxy/data.xml -t $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "deal special table failed!"
			exit;
	fi	
	echo_info "deal special table end!"
	
	# 12. check
	echo_info "check merge data start ...!"
	sh $BASEPATH/optool/check_merge_data.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "check merge data $target failed!"
			exit;
	fi
	echo_info "check merge data end!"
	
	# 13. export user
	echo_info "export user info ...!"
	sh $BASEPATH/optool/exportTmpUser.sh $target
	shell_result=$?
	if [ $shell_result -eq 1 ]; then
			echo_error "export user info $target failed!"
			exit;
	fi
	echo_info "export user info end!"	
	
	# 14. rename database
	echo_prompt "do you confirm to rename db?(y/n)"
	read prompt
	if [ "$prompt" == "y" ]; then
		echo_info "rename old database ..."
		for i in $GROUPLIST; do
			sh $BASEPATH/optool/rename_originDB.sh $i
			shell_result=$?
			if [ $shell_result -eq 1 ]; then
					echo_error "rename old database $i failed!"
					exit;
			fi
		done
		echo_info "rename old database end!"
	fi
	
	# 15. delete lcserver node
	echo_prompt "do you confirm to delete zookeeper node?(y/n)"
	read prompt
	if [ "$prompt" == "y" ]; then
		echo_info "delete lcserver old zookeeper node ..."
		for i in $GROUPLIST; do
			sh $BASEPATH/optool/delete_LC_zookeeper.sh $i
			shell_result=$?
			if [ $shell_result -eq 1 ]; then
					echo_error "delete lcserver $i failed!"
					exit;
			fi
		done
		echo_info "delete lcserver old zookeeper end!" 
	fi
	
	echo_info "done please start server"
}

target=""
mdbip=""	
multi_process=1
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
		-m) 
			((i++));
			eval multi_process=${ARGV[$i]}
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
checkOtherProcess  $target
mergeRun $target $mdbip $multi_process
afterMerge
