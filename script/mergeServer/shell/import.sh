#!/bin/bash

#得到当前文件所在目录
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

USER=$1
PASSWORD=$2
HOST=$3
DBNAME=$4
FILE=$5

#导入数据表
MYSQLDUMP=/home/pirate/programs/mysql/bin/mysqldump
MYSQL=/home/pirate/programs/mysql/bin/mysql

dblist=`$MYSQL -u$USER -p$PASSWORD -h$HOST -e "show databases;" | grep $DBNAME`

dbexist=0
if [ -n "$dblist" ]; then
	echo -e "$DBNAME has in DB $HOST!\n";
	while true;
	do
		echo "please input y/Y to affirm! n/N to exit!";
		read p;
		if [ "$p"x = 'yx' -o "$p"x = 'Yx' ]; then
			dbexist=1
			break;
		elif [ "$p"x = 'nx' -o "$p"x = 'Nx' ]; then
			exit 1;
		else
			continue;
		fi
	done;
fi

if [ $dbexist -eq 0 ]; then
	echo "CREATE DATABASE $DBNAME ON DB $HOST!";
	$MYSQL -u$USER -p$PASSWORD -h$HOST -e "create database if not exists $DBNAME;";
	$MYSQL -u$USER -p$PASSWORD -h$HOST $DBNAME < $FILE
	echo "CREATE DATABASE $DBNAME ON DB $HOST DONE!";
	echo "MODIFY $DBNAME START!";
	for i in `ls $BASEPATH/doc/modify*`; do
		echo "$MYSQL -u$USER -p$PASSWORD -h$HOST $DBNAME < $i;";
		$MYSQL -u$USER -p$PASSWORD -h$HOST $DBNAME < $i;
	done
	echo "MODIFY $DBNAME DONE!";
fi

echo "ADD TMP TABLE FOR MERGE!"
for i in `ls $BASEPATH/doc/tmp*`; do
	echo "$MYSQL -u$USER -p$PASSWORD -h$HOST $DBNAME < $i;";
	$MYSQL -u$USER -p$PASSWORD -h$HOST $DBNAME < $i;
done
echo "ADD TMP TABLE FOR MERGE DONE!"