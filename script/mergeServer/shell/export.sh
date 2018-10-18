#!/bin/bash

USER=$1
PASSWORD=$2
HOST=$3
DBNAME=$4
FILE=$5

#导出数据表
MYSQLDUMP=/home/pirate/programs/mysql/bin/mysqldump
MYSQL=/home/pirate/programs/mysql/bin/mysql

> $FILE
$MYSQLDUMP -u$USER -p$PASSWORD -h$HOST $DBNAME --no-data > $FILE
