#!/bin/bash

#!/bin/sh

#constant
GREP='/bin/grep'
PHP='/home/pirate/programs/php/bin/php'

#get btstore path
FILEPATH='/home/pirate/rpcfw/data/btstore'

for i in `ls $FILEPATH`;
do
	$PHP /home/pirate/rpcfw/script/btstoreString2Int.php $FILEPATH/$i > $FILEPATH/$i.tmp
	mv $FILEPATH/$i.tmp $FILEPATH/$i;
done

echo "STRING2INT DONE!";
