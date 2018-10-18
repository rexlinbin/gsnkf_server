#!/bin/sh

alias btscript='/home/pirate/programs/php/bin/php /home/pirate/rpcfw/lib/ScriptRunner.php -f'

ARGS_DIR=/home/pirate/lcserver/conf
GSC_DIR=/home/pirate/rpcfw/conf/gsc

FILE=$1
if [ "$FILE" = "" -o ! -e $FILE ]; then
	echo "$FILE does not exist"
	exit 0
fi

shift

#execute
last_offset=0;
for k in `grep -r "BOSS_OFFSET" $GSC_DIR | grep -v "svn" | cut -d":" -f2 | grep -Eo '[0-9]+' | sort -n | uniq`; do
	last_offset=`expr $k - $last_offset`
	sleep $last_offset;
	for i in /home/pirate/lcserver/conf/*.args; do
		db=`grep -Eo "\-d [^ ]+" $i|cut -d" " -f2`;
		group=`basename $i|cut -d"." -f1`;
		offset=`grep BOSS_OFFSET $GSC_DIR/$group/Game.cfg.php |egrep -o '[0-9]+' -a`;
		[ $offset -eq $k ] && btscript $FILE -g $group -d $db $@ &
	done
	last_offset=$k;
done
