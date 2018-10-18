#!/bin/bash

#得到当前文件所在目录
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

USER=$1
PASSWORD=$2
HOST=$3
DBNAME=$4

MYSQLDUMP=/home/pirate/programs/mysql/bin/mysqldump
MYSQL=/home/pirate/programs/mysql/bin/mysql

for i in `ls $BASEPATH/doc/suffix*`; do
	$MYSQL -u$USER -p$PASSWORD -h$HOST $DBNAME < $i;
done
