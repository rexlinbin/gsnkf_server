#!/bin/sh

if [ $# -ne 2 ];then
    echo '''usage:
    sh truncate.sh HOST DBNAME'''
    exit 0
fi
HOST=$1
DB=$2
MYSQL=/home/pirate/programs/mysql/bin/mysql
MYSQLDUMP=/home/pirate/programs/mysql/bin/mysqldump
BAKDIR=./backup/sqlbak
UNAME=pirate
PASSWD='-padmin'
ROOT_PASSWD=''

if [ ! -d $BAKDIR/$DB ];then
    mkdir $BAKDIR/$DB
    read -p 'press enter to continues...'
else
    echo 'Folder exists!!you will overwrite it!!'
    read -p 'press enter to continues...'
fi

for table in `ssh $HOST "$MYSQL -uroot $ROOT_PASSWD $DB -e 'show tables'"`; do
        
	if [ $table = "Tables_in_$DB" ]; then
		continue
	fi

	$MYSQLDUMP -u $UNAME $PASSWD  -h$HOST $DB $table  > $BAKDIR/$DB/$table.sql
	if [ $table = "t_random_name" ]; then
			continue
	fi

	if [ $table = "t_mineral" ]; then
		continue
	fi

	ssh $HOST "$MYSQL -uroot $ROOT_PASSWD $DB -e 'truncate $table'"
	echo "truncate $table done"
done


ssh $HOST "$MYSQL -uroot $ROOT_PASSWD $DB -e 'update t_random_name set status = 0 where status != 0;'"
ssh $HOST "$MYSQL -uroot $ROOT_PASSWD $DB -e 'update t_mineral set uid = 0, occupy_time = 0, due_timer = 0;'"

echo "truncate done. remember init !"

